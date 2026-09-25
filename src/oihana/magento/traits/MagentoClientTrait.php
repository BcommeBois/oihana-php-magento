<?php

namespace oihana\magento\traits;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;

use Random\RandomException;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use JsonException;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

use oihana\enums\http\HttpHeader;
use oihana\enums\http\HttpMethod;
use oihana\enums\http\HttpStatusCode;
use oihana\exceptions\http\Error401;
use oihana\exceptions\http\Error404;
use oihana\files\enums\FileMimeType;
use oihana\logging\LoggerTrait;
use oihana\magento\enums\MagentoOption;
use oihana\magento\enums\MagentoParam;
use oihana\magento\exceptions\MagentoRequestException;
use oihana\magento\http\OAuthSigner;
use oihana\reflect\traits\ReflectionTrait;

use oihana\magento\enums\Magento;

use function oihana\files\path\joinPaths;

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
     * Maximum number of attempts for a transient failure: a `429`, a `500`, `502`,
     * `503` or `504`, or a request that received no response (timeout, refused connection).
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
     * @return mixed The decoded JSON response, or null when the response body is empty.
     *
     * @throws Error401
     * @throws Error404
     * @throws GuzzleException
     * @throws MagentoRequestException When the request finally fails.
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
     * Decodes a JSON response body into associative arrays.
     *
     * @param string $body       The raw response body.
     * @param string $endpoint   The called endpoint, for the failure message.
     * @param int    $statusCode The HTTP status of the response, carried by the failure.
     *
     * @return mixed The decoded value, or null when the body is empty.
     *
     * @throws MagentoRequestException When the body is not valid JSON.
     */
    private function decode( string $body , string $endpoint , int $statusCode ) : mixed
    {
        if ( $body === '' )
        {
            return null ;
        }

        try
        {
            return json_decode( $body , true , flags : JSON_THROW_ON_ERROR ) ;
        }
        catch ( JsonException $e )
        {
            $this->error( "Invalid JSON response for endpoint $endpoint: " . $e->getMessage() ) ;
            throw new MagentoRequestException( "Invalid JSON response for endpoint $endpoint" , $statusCode , $e ) ;
        }
    }

    /**
     * Execute an API call with OAuth authentication.
     *
     * This method attempts to send an HTTP request to the given endpoint using the
     * specified method and options, automatically handling OAuth signing and retries
     * for transient errors. It decodes JSON responses into associative arrays.
     *
     * Retry logic:
     * - A transient failure ({@see isRetryable()}) is retried up to `$this->maxRetries`
     *   attempts with exponential backoff (2^attempts seconds).
     * - On 401 Unauthorized, the method logs an OAuth authentication warning
     *   and stops further retries.
     *
     * A request that finally fails never answers null: it throws, so a caller can
     * always tell an empty result apart from an outage.
     *
     * Logging:
     * - Warnings are issued for each failed attempt including the exception message.
     * - Notices indicate wait times between retries.
     * - Errors are logged on the final failure.
     *
     * @param string $endpoint The API endpoint (path relative to the base URI).
     * @param string $method   HTTP method to use (GET, POST, etc.). Defaults to GET.
     * @param array  $options  Request options for GuzzleHttp\Client (headers, query, json, etc.).
     *
     * @return mixed Returns the decoded JSON response as an associative array, or null when the response body is empty.
     *
     * @throws RandomException         If OAuth signature generation fails.
     * @throws GuzzleException         If the HTTP client fails outside a request or connection error.
     * @throws Error404                Magento resource not found (404)
     * @throws Error401                OAuth authentication error (401)
     * @throws MagentoRequestException Retries exhausted, non-retryable status, non-2xx response or invalid JSON body.
     */
    private function execute( string $endpoint , string $method = HttpMethod::GET , array $options = [] ) : mixed
    {
        $attempts = 0;

        while ( $attempts < $this->maxRetries )
        {
            try
            {
                // Build one absolute URL used both for signing and for the request, so the
                // OAuth base string always matches the URL actually called. Guzzle resolves a
                // relative endpoint against base_uri per RFC 3986, which can diverge from a
                // naive baseUri.endpoint concatenation (missing/extra slashes) and break the
                // signature. Passing an absolute URL bypasses that resolution.
                $url = joinPaths( $this->baseUri , $endpoint ) ;

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

                $response = $this->client->request( $method , $url , $options ) ;

                $statusCode   = $response->getStatusCode() ;
                $responseBody = $response->getBody()->getContents() ;

                if ( $statusCode < HttpStatusCode::OK || $statusCode >= HttpStatusCode::MULTIPLE_CHOICES )
                {
                    $this->error( "Non-success status code $statusCode for endpoint $endpoint" ) ;
                    throw new MagentoRequestException( "Non-success status code $statusCode for endpoint $endpoint" , $statusCode ) ;
                }

                return $this->decode( $responseBody , $endpoint , $statusCode ) ;
            }
            catch ( ConnectException | RequestException $e )
            {
                $attempts++ ;

                $statusCode = $e instanceof RequestException && $e->hasResponse()
                            ? $e->getResponse()->getStatusCode()
                            : MagentoRequestException::NO_RESPONSE ;

                $this->warning("API error (attempt $attempts/$this->maxRetries): " . $e->getMessage() ) ;

                if ( $statusCode === HttpStatusCode::NOT_FOUND )
                {
                    throw new Error404( "Magento resource not found (404) for endpoint $endpoint" ) ;
                }

                if ( $statusCode === HttpStatusCode::UNAUTHORIZED )
                {
                    $this->warning( "OAuth authentication error - please check your tokens" ) ;
                    throw new Error401( "OAuth authentication error - please check your tokens" ) ;
                }

                if ( $this->isRetryable( $statusCode ) && $attempts < $this->maxRetries )
                {
                    $waitTime = pow(2, $attempts);
                    $this->notice( sprintf( "⏳ Waiting %d before retry..." , $waitTime ) ) ;
                    $this->waitBeforeRetry( $waitTime ) ;
                    continue;
                }

                $this->error( sprintf( "Final failure after %d attempt(s)" , $attempts ) ) ;

                throw new MagentoRequestException
                (
                    sprintf( "Magento request failed after %d attempt(s) for endpoint %s: %s" , $attempts , $endpoint , $e->getMessage() ) ,
                    $statusCode ,
                    $e
                ) ;
            }
        }

        // @codeCoverageIgnoreStart
        // The while loop always returns or throws inside its body; this final return
        // is only reached when `maxRetries` is lower than 1 and nothing is sent.
        return null;
        // @codeCoverageIgnoreEnd
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
     * @return bool True when Magento answered with a success status, false when the request finally failed.
     *
     * @throws Error401
     * @throws Error404
     * @throws GuzzleException
     * @throws RandomException
     */
    public function isConnected( string $endpoint = 'modules' ):bool
    {
        try
        {
            $this->execute( $endpoint ) ;
            return true ;
        }
        catch ( MagentoRequestException )
        {
            return false ;
        }
    }

    // ----------- Protected

    /**
     * Indicates whether a failed attempt is transient and worth retrying.
     *
     * Retryable: no response at all ({@see MagentoRequestException::NO_RESPONSE} — timeout,
     * refused connection), `429 Too Many Requests`, and the `500`, `502`, `503`, `504`
     * server errors. Any other status fails at once.
     *
     * @param int $statusCode The HTTP status of the failed attempt, or {@see MagentoRequestException::NO_RESPONSE}.
     *
     * @return bool True when the attempt may be retried.
     */
    protected function isRetryable( int $statusCode ):bool
    {
        return in_array
        (
            $statusCode ,
            [
                MagentoRequestException::NO_RESPONSE ,
                HttpStatusCode::TOO_MANY_REQUESTS ,
                HttpStatusCode::INTERNAL_SERVER_ERROR ,
                HttpStatusCode::BAD_GATEWAY ,
                HttpStatusCode::SERVICE_UNAVAILABLE ,
                HttpStatusCode::GATEWAY_TIMEOUT ,
            ] ,
            true
        ) ;
    }

    /**
     * Waits for the given number of seconds between retry attempts.
     *
     * Isolated in its own method so the exponential-backoff delay can be overridden
     * (e.g. made instantaneous) by subclasses and tests, without changing the retry logic.
     *
     * @param int $seconds The number of seconds to wait.
     *
     * @return void
     */
    protected function waitBeforeRetry( int $seconds ):void
    {
        sleep( $seconds ) ;
    }

    // ----------- Private

    private OAuthSigner $signer ;

    private string $baseUri ;

    private Client $client ;
}