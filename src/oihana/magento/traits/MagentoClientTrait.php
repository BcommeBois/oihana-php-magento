<?php

namespace oihana\magento\traits;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;

use Random\RandomException;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

use oihana\enums\http\HttpHeader;
use oihana\enums\http\HttpMethod;
use oihana\exceptions\http\Error401;
use oihana\exceptions\http\Error404;
use oihana\files\enums\FileMimeType;
use oihana\logging\LoggerTrait;
use oihana\magento\enums\MagentoOption;
use oihana\magento\enums\MagentoParam;
use oihana\magento\http\OAuthSigner;
use oihana\reflect\traits\ReflectionTrait;

use oihana\magento\enums\Magento;

trait MagentoClientTrait
{
    /**
     * Creates a new MagentoClient instance.
     *
     * @param Container $container The DI container used to resolve the logger.
     * @param array     $init      Optional configuration keyed by {@see Magento} constants:
     *                             `consumerKey`, `consumerSecret`, `token`, `tokenSecret`,
     *                             `baseUri`, `maxRetries`, an optional Guzzle `handler`
     *                             (useful for testing or custom transports) and any logger options.
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct( Container $container , array $init = [] )
    {
        $this->initializeOauth( $init );
        $this->initializeLogger( $init , $container , false ) ;

        $this->maxRetries = $init[ Magento::MAX_RETRIES ] ?? 3 ;
        $this->baseUri    = $init[ Magento::BASE_URI    ] ?? '' ;

        $config =
        [
            Magento::BASE_URI => $this->baseUri ,
            Magento::TIMEOUT  => 30 ,
            Magento::VERIFY   => true ,
            Magento::HEADERS  =>
            [
                HttpHeader::CONTENT_TYPE => FileMimeType::JSON ,
                HttpHeader::ACCEPT       => FileMimeType::JSON ,
            ]
        ];

        // Optional Guzzle handler (e.g. a MockHandler stack) for testing or custom transports.
        if ( isset( $init[ Magento::HANDLER ] ) )
        {
            $config[ Magento::HANDLER ] = $init[ Magento::HANDLER ] ;
        }

        $this->client = new Client( $config ) ;
    }

    use LoggerTrait ,
        ReflectionTrait ;

    /**
     * Maximum number of attempts for a transient (5xx / timeout) request.
     * @var int
     */
    public int $maxRetries = 3 ;

    /**
     * Call a generic API endpoint.
     *
     * @param string     $endpoint    The API endpoint (path relative to the base URI).
     * @param string     $method      HTTP method to use (GET, POST, PUT, DELETE, …).
     * @param mixed|null $data        Optional request body, sent as JSON when provided.
     * @param array      $queryParams Optional query-string parameters.
     *
     * @return mixed The decoded JSON response, or null on failure.
     *
     * @throws Error401
     * @throws Error404
     * @throws GuzzleException
     * @throws RandomException
     */
    public function call( string $endpoint , string $method , mixed $data = null , array $queryParams = [] ) : mixed
    {
        $options = [];

        if ( $data !== null )
        {
            $options[ MagentoOption::JSON ] = $data ;
        }

        if ( !empty( $queryParams ) )
        {
            $options[ MagentoOption::QUERY ] = $queryParams ;
        }

        return $this->execute( $endpoint , $method , $options ) ;
    }

    /**
     * Execute an API call with OAuth authentication.
     *
     * This method attempts to send an HTTP request to the given endpoint using the
     * specified method and options, automatically handling OAuth signing and retries
     * for transient errors. It decodes JSON responses into associative arrays.
     *
     * Retry logic:
     * - On 5xx server errors or timeout exceptions, the request is retried up to
     *   `$this->maxRetries` times with exponential backoff (2^attempts seconds).
     * - On 401 Unauthorized, the method logs an OAuth authentication warning
     *   and stops further retries.
     *
     * Logging:
     * - Warnings are issued for each failed attempt including the exception message.
     * - Notices indicate wait times between retries.
     * - Errors are logged when all retries fail.
     *
     * @param string $endpoint The API endpoint (path relative to the base URI).
     * @param string $method   HTTP method to use (GET, POST, etc.). Defaults to GET.
     * @param array  $options  Request options for GuzzleHttp\Client (headers, query, json, etc.).
     *
     * @return mixed Returns the decoded JSON response as an associative array, or null on failure.
     *
     * @throws RandomException   If OAuth signature generation fails.
     * @throws GuzzleException   If the HTTP client encounters an error outside retryable cases.
     * @throws Error404          Magento resource not found (404)
     * @throws Error401          OAuth authentication error (401)
     */
    private function execute( string $endpoint , string $method = HttpMethod::GET , array $options = [] ) : mixed
    {
        $attempts = 0;

        while ( $attempts < $this->maxRetries )
        {
            try
            {
                $url = $this->baseUri . $endpoint ;

                $signatureParams = [];
                if ( $method === HttpMethod::GET && isset( $options[ MagentoParam::QUERY ] ) )
                {
                    $signatureParams = $options[ MagentoParam::QUERY ] ;
                }

                $authHeader = $this->signer->createAuthHeader( $method , $url , $signatureParams ) ;

                if ( !isset( $options[ MagentoOption::HEADERS ] ) )
                {
                    $options[ MagentoOption::HEADERS ] = [];
                }

                $options[ MagentoOption::HEADERS ][ HttpHeader::AUTHORIZATION ] = $authHeader ;

                $response = $this->client->request( $method , $endpoint , $options ) ;

                $statusCode   = $response->getStatusCode() ;
                $responseBody = $response->getBody()->getContents() ;

                if ( $statusCode >= 200 && $statusCode < 300 )
                {
                    return json_decode( $responseBody , true ) ;
                }
                else
                {
                    $this->warning( 'Non-success status code: ' . $statusCode ) ;
                    return null;
                }
            }
            catch ( RequestException $e )
            {
                $attempts++ ;

                $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0 ;

                $this->warning("API error (attempt $attempts/$this->maxRetries): " . $e->getMessage() ) ;

                if ( $statusCode === 404 )
                {
                    throw new Error404( "Magento resource not found (404) for endpoint $endpoint" ) ;
                }

                if ( $statusCode === 401 )
                {
                    $this->warning( "OAuth authentication error - please check your tokens" ) ;
                    throw new Error401( "OAuth authentication error - please check your tokens" ) ;
                }

                if ( in_array( $statusCode , [ 500 , 502 , 503 , 504 ] ) || str_contains( $e->getMessage() , 'timeout' ) )
                {
                    if ( $attempts < $this->maxRetries )
                    {
                        $waitTime = pow(2, $attempts);
                        $this->notice( sprintf( "⏳ Waiting %d before retry..." , $waitTime ) ) ;
                        sleep( $waitTime ) ;
                        continue;
                    }
                }

                $this->error( sprintf( "Final failure after %d attempts" , $this->maxRetries ) ) ;

                return null;
            }
        }

        return null;
    }

    /**
     * Initializes the OAuth signer from the given configuration.
     *
     * @param array $init Configuration keyed by {@see Magento} constants: `consumerKey`, `consumerSecret`, `token`, `tokenSecret`.
     *                    Missing keys default to an empty string.
     *
     * @return $this
     */
    public function initializeOauth( array $init = [] ):static
    {
        $this->signer = new OAuthSigner
        (
            consumerKey       : $init[ Magento::CONSUMER_KEY    ] ?? '' ,
            consumerSecret    : $init[ Magento::CONSUMER_SECRET ] ?? '' ,
            accessToken       : $init[ Magento::TOKEN           ] ?? '' ,
            accessTokenSecret : $init[ Magento::TOKEN_SECRET    ] ?? ''
        ) ;
        return $this ;
    }

    /**
     * Tests the connection by hitting a lightweight endpoint.
     *
     * @param string $endpoint The endpoint used for the probe (default `modules`).
     *
     * @return bool True if the request returned a decoded response, false otherwise.
     *
     * @throws Error401
     * @throws Error404
     * @throws GuzzleException
     * @throws RandomException
     */
    public function isConnected( string $endpoint = 'modules' ):bool
    {
        return $this->execute( $endpoint ) !== null ;
    }

    // ----------- Private

    private OAuthSigner $signer ;

    private string $baseUri ;

    private Client $client ;
}