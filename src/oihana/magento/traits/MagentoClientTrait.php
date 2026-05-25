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
    use LoggerTrait ,
        ReflectionTrait ;

    /**
     * Creates a new MagentoClient instance.
     * @param Container $container
     * @param array $init
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
        $this->client     = new Client
        ([
            Magento::BASE_URI => $this->baseUri ,
            Magento::TIMEOUT  => 30 ,
            Magento::VERIFY   => true ,
            Magento::HEADERS  =>
            [
                HttpHeader::CONTENT_TYPE => FileMimeType::JSON ,
                HttpHeader::ACCEPT       => FileMimeType::JSON ,
            ]
        ]) ;
    }

    /**
     * @var int|mixed
     */
    public int $maxRetries = 3 ;

    /**
     * Call a generic API endpoint.
     *
     * @param string $endpoint
     * @param string $method
     * @param mixed|null $data
     * @param array $queryParams
     *
     * @return mixed
     *
     * @throws Error401
     * @throws Error404
     * @throws GuzzleException
     * @throws RandomException
     */
    public function call( string $endpoint , string $method , mixed $data = null , array $queryParams = [] ) : mixed
    {
        $options = [];

        if ( $data )
        {
            $options[ MagentoOption::JSON ] = $data ;
        }

        if ( !empty( $queryParams ) )
        {
            $options[ MagentoOption::QUERY ] = $queryParams ;
        }

        return $this->execute( $method , $endpoint , $options ) ;
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
     * - Warnings are issued for each failed attempt including HTTP status code and
     *   exception messages.
     * - Info logs provide details of the server response when available.
     * - Notices indicate wait times between retries.
     * - Errors are logged when all retries fail.
     *
     * @param string $endpoint The API endpoint (path relative to the base URI).
     * @param string $method HTTP method to use (GET, POST, etc.). Defaults to GET.
     * @param array $options Request options for GuzzleHttp\Client (headers, query, json, etc.).
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

                // echo 'URL     : ' . $url . PHP_EOL;
                // echo 'Method  : ' . $method . PHP_EOL;
                // echo 'Options : ' . json_encode( $options , JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;

                $response = $this->client->request( $method , $endpoint , $options ) ;

                $statusCode   = $response->getStatusCode() ;
                $responseBody = $response->getBody()->getContents() ;

                // echo 'Status Code: '   . $statusCode . PHP_EOL;
                // echo 'Response Body: ' . $responseBody . PHP_EOL;

                if ( $statusCode >= 200 && $statusCode < 300 )
                {
                    return json_decode( $responseBody , true ) ;
                }
                else
                {
                    $this->warning( 'Non-success status code: ' . $statusCode ) ;
                    // echo 'Non-success status code: ' . $statusCode . PHP_EOL ;
                    return null;
                }
            }
            catch ( RequestException $e )
            {
                $attempts++ ;

                $statusCode   = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0 ;
                $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';

                $this->warning("API error (attempt $attempts/$this->maxRetries): " . $e->getMessage() ) ;

                // echo "API error (attempt $attempts/$this->maxRetries): " . $e->getMessage() . PHP_EOL;
                // echo "Status Code: " . $statusCode . PHP_EOL;
                // echo "Response Body: " . $responseBody . PHP_EOL;

                if ( $statusCode === 404 )
                {
                    throw new Error404( "Magento resource not found (404) for endpoint $endpoint" ) ;
                }

                if ( $statusCode === 401 )
                {
                    $this->warning( "OAuth authentication error - please check your tokens" ) ;
                    if ( $e->hasResponse() )
                    {
                        $this->info( "Details: " . $e->getResponse()->getBody());
                    }

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
     * Initialize the
     * @param array $init
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
     * Test the connection.
     * @return bool Return true if the client is connected.
     * @throws Error401
     * @throws Error404
     * @throws GuzzleException
     * @throws RandomException
     */
    public function isConnected( string $endpoint = 'modules' ):bool
    {
        $result = $this->execute( $endpoint ) ;
        if ( $result !== null )
        {
            return true;
        }
        return false;
    }

    // ----------- Private

    private OAuthSigner $signer ;

    private string $baseUri ;

    private Client $client ;
}