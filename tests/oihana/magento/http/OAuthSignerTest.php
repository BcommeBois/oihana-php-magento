<?php

namespace tests\oihana\magento\http;

use PHPUnit\Framework\TestCase;

use Random\RandomException;

use oihana\magento\http\OAuthSigner;
use oihana\enums\http\AuthScheme;
use oihana\enums\http\OAuthParameters;
use oihana\enums\http\OAuthSignatureMethod;

/**
 * Unit coverage for {@see \oihana\magento\http\OAuthSigner}.
 *
 * The signer builds an OAuth 1.0a (RFC 5849) `Authorization` header value.
 * Two surfaces are checked here :
 *
 *  - {@see OAuthSigner::createAuthHeader()} — must produce a value that
 *    starts with the `OAuth ` scheme prefix, exposes every required
 *    OAuth parameter as `key="value"` and uses `rawurlencode` on both
 *    keys and values.
 *  - {@see OAuthSigner::generateSignature()} — must return the canonical
 *    OAuth parameter set plus a non-empty `oauth_signature` produced by
 *    HMAC-SHA256 over the RFC 5849 §3.4.1 base string.
 */
class OAuthSignerTest extends TestCase
{
    private const string CONSUMER_KEY        = 'consumer-key' ;
    private const string CONSUMER_SECRET     = 'consumer-secret' ;
    private const string ACCESS_TOKEN        = 'access-token' ;
    private const string ACCESS_TOKEN_SECRET = 'access-token-secret' ;

    private OAuthSigner $signer ;

    protected function setUp() :void
    {
        $this->signer = new OAuthSigner
        (
            consumerKey       : self::CONSUMER_KEY        ,
            consumerSecret    : self::CONSUMER_SECRET     ,
            accessToken       : self::ACCESS_TOKEN        ,
            accessTokenSecret : self::ACCESS_TOKEN_SECRET
        ) ;
    }

    /**
     * Regression guard for the original bug : the header must start
     * with the `OAuth ` scheme prefix (RFC 5849 §3.5.1), not a header
     * name.
     *
     * @throws RandomException
     */
    public function testCreateAuthHeaderStartsWithOAuthSchemePrefix() :void
    {
        $header = $this->signer->createAuthHeader( 'GET' , 'https://api.example.com/resource' ) ;

        $this->assertStringStartsWith( AuthScheme::prefix( AuthScheme::OAUTH ) , $header ) ;
        $this->assertStringStartsWith( 'OAuth ' , $header ) ;
    }

    /**
     * The header value must expose every mandatory OAuth 1.0a parameter
     * encoded as `key="value"` and separated by `, `.
     *
     * @throws RandomException
     */
    public function testCreateAuthHeaderExposesEveryRequiredParameter() :void
    {
        $header = $this->signer->createAuthHeader( 'GET' , 'https://api.example.com/resource' ) ;

        $required =
        [
            OAuthParameters::OAUTH_CONSUMER_KEY     ,
            OAuthParameters::OAUTH_TOKEN            ,
            OAuthParameters::OAUTH_SIGNATURE_METHOD ,
            OAuthParameters::OAUTH_TIMESTAMP        ,
            OAuthParameters::OAUTH_NONCE            ,
            OAuthParameters::OAUTH_VERSION          ,
            OAuthParameters::OAUTH_SIGNATURE        ,
        ] ;

        foreach ( $required as $key )
        {
            $this->assertMatchesRegularExpression
            (
                '/\b' . preg_quote( $key , '/' ) . '="[^"]*"/' ,
                $header ,
                sprintf( 'Missing OAuth parameter "%s" in header value.' , $key )
            ) ;
        }
    }

    /**
     * OAuth values exposed in the header (RFC 5849 §3.5.1 only carries
     * `oauth_*` parameters, never request parameters) must be
     * `rawurlencode`d per RFC 5849 §3.6. We verify it through a
     * `consumerKey` that contains reserved characters.
     *
     * @throws RandomException
     */
    public function testCreateAuthHeaderRawurlencodesValues() :void
    {
        $signer = new OAuthSigner
        (
            consumerKey       : 'key+with/special chars' ,
            consumerSecret    : self::CONSUMER_SECRET ,
            accessToken       : self::ACCESS_TOKEN ,
            accessTokenSecret : self::ACCESS_TOKEN_SECRET
        ) ;

        $header = $signer->createAuthHeader( 'GET' , 'https://api.example.com/resource' ) ;

        $this->assertStringContainsString
        (
            OAuthParameters::OAUTH_CONSUMER_KEY . '="key%2Bwith%2Fspecial%20chars"' ,
            $header
        ) ;
    }

    /**
     * `generateSignature()` must return the canonical OAuth parameter
     * set plus a non-empty signature.
     *
     * @throws RandomException
     */
    public function testGenerateSignatureReturnsCanonicalParameterSet() :void
    {
        $params = $this->signer->generateSignature( 'GET' , 'https://api.example.com/resource' ) ;

        $this->assertSame( self::CONSUMER_KEY , $params[ OAuthParameters::OAUTH_CONSUMER_KEY ] ) ;
        $this->assertSame( self::ACCESS_TOKEN , $params[ OAuthParameters::OAUTH_TOKEN ] ) ;
        $this->assertSame( OAuthSignatureMethod::HMAC_SHA256 , $params[ OAuthParameters::OAUTH_SIGNATURE_METHOD ] ) ;
        $this->assertSame( OAuthSigner::VERSION , $params[ OAuthParameters::OAUTH_VERSION ] ) ;

        $this->assertIsInt( $params[ OAuthParameters::OAUTH_TIMESTAMP ] ) ;
        $this->assertGreaterThan( 0 , $params[ OAuthParameters::OAUTH_TIMESTAMP ] ) ;

        $this->assertMatchesRegularExpression( '/^[0-9a-f]{32}$/' , $params[ OAuthParameters::OAUTH_NONCE ] ) ;

        $this->assertNotEmpty( $params[ OAuthParameters::OAUTH_SIGNATURE ] ) ;
    }

    /**
     * The signature must be deterministic given identical timestamp,
     * nonce and parameters : same input → same HMAC-SHA256 output. We
     * recompute the signature from the parameter set returned by the
     * signer and compare it.
     *
     * @throws RandomException
     */
    public function testGenerateSignatureIsDeterministicForFixedInputs() :void
    {
        $params = $this->signer->generateSignature( 'GET' , 'https://api.example.com/resource' ) ;

        $signature = $params[ OAuthParameters::OAUTH_SIGNATURE ] ;

        unset( $params[ OAuthParameters::OAUTH_SIGNATURE ] ) ;
        ksort( $params ) ;

        $paramString = http_build_query( $params , '' , '&' , PHP_QUERY_RFC3986 ) ;
        $baseString  = 'GET&' . rawurlencode( 'https://api.example.com/resource' ) . '&' . rawurlencode( $paramString ) ;
        $signingKey  = rawurlencode( self::CONSUMER_SECRET ) . '&' . rawurlencode( self::ACCESS_TOKEN_SECRET ) ;

        $expected = base64_encode( hash_hmac( 'sha256' , $baseString , $signingKey , true ) ) ;

        $this->assertSame( $expected , $signature ) ;
    }
}
