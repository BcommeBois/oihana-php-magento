<?php

namespace tests\oihana\magento\traits;

use DI\Container;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\RequestInterface;

use oihana\enums\http\HttpMethod;
use oihana\exceptions\http\Error401;
use oihana\exceptions\http\Error404;

use oihana\magento\MagentoClient;
use oihana\magento\enums\Magento;
use oihana\magento\exceptions\MagentoRequestException;

/**
 * Test double exposing the retry backoff as an instant, observable no-op,
 * so the 5xx retry path can be covered without a real `sleep()`.
 */
class RecordingMagentoClient extends MagentoClient
{
    /** @var int Total number of seconds the client was asked to wait between retries. */
    public int $waited = 0 ;

    protected function waitBeforeRetry( int $seconds ) : void
    {
        $this->waited += $seconds ;
        parent::waitBeforeRetry( 0 ) ; // exercise the real seam instantly (sleep(0))
    }
}

/**
 * Unit coverage for {@see \oihana\magento\traits\MagentoClientTrait}.
 *
 * The HTTP transport is replaced by a Guzzle {@see MockHandler} injected
 * through the `handler` configuration key, so no live Magento instance is
 * required. A history middleware records the outgoing requests, letting us
 * assert the method, URI, body and headers actually produced by the client.
 */
class MagentoClientTraitTest extends TestCase
{
    private const string BASE_URI = 'https://shop.example.com/rest/V1/' ;

    /**
     * Recorded Guzzle transactions ({@see Middleware::history()}).
     * @var array<int,array>
     */
    private array $history = [] ;

    /**
     * Builds a MagentoClient whose transport is a queue of canned responses.
     *
     * @param Response[] $responses Responses returned in order by the mock handler.
     * @param array      $init      Extra configuration merged into the client init.
     */
    private function makeClient( array $responses , array $init = [] ) : MagentoClient
    {
        $this->history = [] ;

        $stack = HandlerStack::create( new MockHandler( $responses ) ) ;
        $stack->push( Middleware::history( $this->history ) ) ;

        return new MagentoClient
        (
            new Container() ,
            [
                Magento::BASE_URI => self::BASE_URI ,
                Magento::HANDLER  => $stack ,
                ...$init ,
            ]
        ) ;
    }

    /**
     * Builds a {@see RecordingMagentoClient} (instant, observable backoff) whose
     * transport is a queue of canned responses or transport exceptions.
     *
     * @param array $queue      Responses or exceptions returned in order by the mock handler.
     * @param int   $maxRetries The maximum number of attempts.
     */
    private function makeRecordingClient( array $queue , int $maxRetries = 2 ) : RecordingMagentoClient
    {
        $this->history = [] ;

        $stack = HandlerStack::create( new MockHandler( $queue ) ) ;
        $stack->push( Middleware::history( $this->history ) ) ;

        return new RecordingMagentoClient
        (
            new Container() ,
            [
                Magento::BASE_URI    => self::BASE_URI ,
                Magento::HANDLER     => $stack ,
                Magento::MAX_RETRIES => $maxRetries ,
            ]
        ) ;
    }

    /**
     * @param string $message The transport failure message.
     *
     * @return ConnectException A connection failure that carries no response (timeout, refused connection).
     */
    private function connectionFailure( string $message = 'cURL error 28: Operation timed out' ) : ConnectException
    {
        return new ConnectException( $message , new Request( HttpMethod::GET , self::BASE_URI ) ) ;
    }

    /**
     * @return RequestInterface The last request captured by the history middleware.
     */
    private function lastRequest() : RequestInterface
    {
        return $this->history[ array_key_last( $this->history ) ][ 'request' ] ;
    }

    /**
     * Regression guard : `call()` must forward `$endpoint` and `$method` to
     * `execute()` in the right order. A swap would send the request to the
     * wrong URL with the wrong verb.
     */
    public function testCallTargetsTheCorrectEndpointAndMethod() : void
    {
        $client = $this->makeClient( [ new Response( 200 , [] , '{"ok":true}' ) ] ) ;

        $client->call( 'products' , HttpMethod::POST , [ 'sku' => 'X' ] ) ;

        $request = $this->lastRequest() ;

        $this->assertSame( HttpMethod::POST , $request->getMethod() ) ;
        $this->assertStringEndsWith( '/rest/V1/products' , $request->getUri()->getPath() ) ;
    }

    /**
     * The URL is built once (via `joinPaths`) and used both for OAuth signing
     * and for the actual request. A `baseUri` without a trailing slash must
     * still resolve to the full path — not Guzzle's RFC 3986 last-segment
     * replacement (`/rest/products`) nor a naive concatenation
     * (`/rest/V1products`). Either divergence would break the signature.
     */
    public function testBaseUriWithoutTrailingSlashStillTargetsFullPath() : void
    {
        $client = $this->makeClient
        (
            [ new Response( 200 , [] , '{}' ) ] ,
            [ Magento::BASE_URI => 'https://shop.example.com/rest/V1' ]
        ) ;

        $client->call( 'products' , HttpMethod::GET ) ;

        $this->assertSame( '/rest/V1/products' , $this->lastRequest()->getUri()->getPath() ) ;
    }

    /**
     * An endpoint with a leading slash must not escape the base path.
     */
    public function testLeadingSlashEndpointStaysUnderBasePath() : void
    {
        $client = $this->makeClient( [ new Response( 200 , [] , '{}' ) ] ) ;

        $client->call( '/products' , HttpMethod::GET ) ;

        $this->assertSame( '/rest/V1/products' , $this->lastRequest()->getUri()->getPath() ) ;
    }

    /**
     * A falsy-but-valid JSON body (here `0`) must still be sent : the truthy
     * check used previously dropped it silently.
     */
    public function testCallSendsFalsyDataAsJsonBody() : void
    {
        $client = $this->makeClient( [ new Response( 200 , [] , '{}' ) ] ) ;

        $client->call( 'products' , HttpMethod::POST , 0 ) ;

        $this->assertSame( '0' , (string) $this->lastRequest()->getBody() ) ;
    }

    /**
     * `call()` forwards its `$queryParams` onto the request query string.
     */
    public function testCallForwardsQueryParameters() : void
    {
        $client = $this->makeClient( [ new Response( 200 , [] , '{}' ) ] ) ;

        $client->call( 'products' , HttpMethod::GET , null , [ 'page' => 2 , 'limit' => 50 ] ) ;

        $query = urldecode( $this->lastRequest()->getUri()->getQuery() ) ;

        $this->assertStringContainsString( 'page=2' , $query ) ;
        $this->assertStringContainsString( 'limit=50' , $query ) ;
    }

    /**
     * A 2xx response body must be JSON-decoded into an associative array.
     */
    public function testExecuteDecodesJsonResponseOnSuccess() : void
    {
        $client = $this->makeClient( [ new Response( 200 , [] , '{"sku":"ABC","price":9}' ) ] ) ;

        $result = $client->call( 'products/ABC' , HttpMethod::GET ) ;

        $this->assertSame( [ 'sku' => 'ABC' , 'price' => 9 ] , $result ) ;
    }

    /**
     * Each request must carry an OAuth 1.0a `Authorization` header.
     */
    public function testRequestIsSignedWithOAuthHeader() : void
    {
        $client = $this->makeClient
        (
            [ new Response( 200 , [] , '{}' ) ] ,
            [
                Magento::CONSUMER_KEY    => 'ck' ,
                Magento::CONSUMER_SECRET => 'cs' ,
                Magento::TOKEN           => 'tk' ,
                Magento::TOKEN_SECRET    => 'ts' ,
            ]
        ) ;

        $client->call( 'products' , HttpMethod::GET ) ;

        $this->assertStringStartsWith( 'OAuth ' , $this->lastRequest()->getHeaderLine( 'Authorization' ) ) ;
    }

    /**
     * A 404 response must be translated into an {@see Error404}.
     */
    public function testNotFoundResponseThrowsError404() : void
    {
        $this->expectException( Error404::class ) ;

        $client = $this->makeClient( [ new Response( 404 , [] , '{"message":"not found"}' ) ] ) ;
        $client->call( 'products/UNKNOWN' , HttpMethod::GET ) ;
    }

    /**
     * A 401 response must be translated into an {@see Error401}.
     */
    public function testUnauthorizedResponseThrowsError401() : void
    {
        $this->expectException( Error401::class ) ;

        $client = $this->makeClient( [ new Response( 401 , [] , '{}' ) ] ) ;
        $client->call( 'products' , HttpMethod::GET ) ;
    }

    /**
     * `isConnected()` returns true when the probe endpoint answers with success.
     */
    public function testIsConnectedReturnsTrueOnSuccess() : void
    {
        $client = $this->makeClient( [ new Response( 200 , [] , '{"modules":[]}' ) ] ) ;

        $this->assertTrue( $client->isConnected() ) ;
    }

    /**
     * `isConnected()` returns false when the server keeps failing. `maxRetries`
     * is set to 1 to avoid the exponential backoff sleep.
     */
    public function testIsConnectedReturnsFalseOnServerError() : void
    {
        $client = $this->makeClient
        (
            [ new Response( 500 , [] , 'boom' ) ] ,
            [ Magento::MAX_RETRIES => 1 ]
        ) ;

        $this->assertFalse( $client->isConnected() ) ;
    }

    /**
     * A retryable 5xx must trigger the exponential-backoff path (`waitBeforeRetry()`
     * then `continue`) until `maxRetries` is exhausted, after which `execute()`
     * throws a {@see MagentoRequestException} carrying the last status. It never
     * answers null: a caller must not mistake an outage for an empty result.
     */
    public function testServerErrorRetriesWithBackoffThenThrows() : void
    {
        $client = $this->makeRecordingClient
        ([
            new Response( 503 , [] , 'unavailable' ) ,
            new Response( 503 , [] , 'unavailable' ) ,
        ]) ;

        try
        {
            $client->call( 'products' , HttpMethod::GET ) ;
            $this->fail( 'a request that finally fails must throw' ) ;
        }
        catch ( MagentoRequestException $e )
        {
            $this->assertSame( 503 , $e->getCode() ) ;
            $this->assertCount( 2 , $this->history ) ;
            $this->assertGreaterThan( 0 , $client->waited , 'the exponential-backoff retry path must have run' ) ;
        }
    }

    /**
     * A non-2xx response that Guzzle does not raise as an exception (here a `304`,
     * which is neither an error nor a followed redirect) must throw, with its status.
     */
    public function testNonSuccessStatusWithoutExceptionThrows() : void
    {
        $client = $this->makeClient( [ new Response( 304 , [] , '' ) ] ) ;

        $this->expectException( MagentoRequestException::class ) ;
        $this->expectExceptionCode( 304 ) ;

        $client->call( 'products/ABC' , HttpMethod::GET ) ;
    }

    /**
     * A connection failure (timeout, refused connection) carries no response:
     * it is transient, so it is retried, and the next success is returned.
     */
    public function testConnectionFailureIsRetried() : void
    {
        $client = $this->makeRecordingClient
        ([
            $this->connectionFailure() ,
            new Response( 200 , [] , '{"sku":"ABC"}' ) ,
        ]) ;

        $this->assertSame( [ 'sku' => 'ABC' ] , $client->call( 'products/ABC' , HttpMethod::GET ) ) ;
        $this->assertGreaterThan( 0 , $client->waited ) ;
    }

    /**
     * When every attempt fails to get a response, the exception carries
     * {@see MagentoRequestException::NO_RESPONSE} and chains the transport failure.
     */
    public function testConnectionFailureThrowsWithoutStatusOnceRetriesAreExhausted() : void
    {
        $client = $this->makeRecordingClient
        ([
            $this->connectionFailure() ,
            $this->connectionFailure() ,
        ]) ;

        try
        {
            $client->call( 'products' , HttpMethod::GET ) ;
            $this->fail( 'a request that never gets a response must throw' ) ;
        }
        catch ( MagentoRequestException $e )
        {
            $this->assertSame( MagentoRequestException::NO_RESPONSE , $e->getCode() ) ;
            $this->assertInstanceOf( ConnectException::class , $e->getPrevious() ) ;
            $this->assertCount( 2 , $this->history ) ;
        }
    }

    /**
     * `429 Too Many Requests` is transient: it is retried, and the next success is returned.
     */
    public function testTooManyRequestsIsRetried() : void
    {
        $client = $this->makeRecordingClient
        ([
            new Response( 429 , [] , '{"message":"slow down"}' ) ,
            new Response( 200 , [] , '{"sku":"ABC"}' ) ,
        ]) ;

        $this->assertSame( [ 'sku' => 'ABC' ] , $client->call( 'products/ABC' , HttpMethod::GET ) ) ;
        $this->assertCount( 2 , $this->history ) ;
    }

    /**
     * A client error other than 401 / 404 / 429 is not transient: it throws
     * at the first attempt, without any retry.
     */
    public function testClientErrorThrowsAtTheFirstAttempt() : void
    {
        $client = $this->makeRecordingClient
        ([
            new Response( 400 , [] , '{"message":"bad request"}' ) ,
            new Response( 200 , [] , '{}' ) ,
        ]) ;

        try
        {
            $client->call( 'products' , HttpMethod::GET ) ;
            $this->fail( 'a client error must throw' ) ;
        }
        catch ( MagentoRequestException $e )
        {
            $this->assertSame( 400 , $e->getCode() ) ;
            $this->assertCount( 1 , $this->history ) ;
            $this->assertSame( 0 , $client->waited ) ;
        }
    }

    /**
     * A success status whose body is not valid JSON must throw, not answer null.
     */
    public function testInvalidJsonBodyThrows() : void
    {
        $client = $this->makeClient( [ new Response( 200 , [] , '<html>maintenance</html>' ) ] ) ;

        $this->expectException( MagentoRequestException::class ) ;
        $this->expectExceptionCode( 200 ) ;

        $client->call( 'products' , HttpMethod::GET ) ;
    }

    /**
     * An empty body (`204 No Content`) is a real answer: it decodes to null.
     */
    public function testEmptyBodyReturnsNull() : void
    {
        $client = $this->makeClient( [ new Response( 204 , [] , '' ) ] ) ;

        $this->assertNull( $client->call( 'products/ABC' , HttpMethod::DELETE ) ) ;
    }
}
