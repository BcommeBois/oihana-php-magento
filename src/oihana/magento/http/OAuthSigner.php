<?php

namespace oihana\magento\http;

use oihana\enums\HashAlgorithm;
use oihana\enums\http\AuthScheme;
use oihana\enums\http\OAuthParameters;
use oihana\enums\http\OAuthSignatureMethod;
use Random\RandomException;

/**
 * Utility class to generate OAuth 1.0a Authorization headers for HTTP requests.
 * Provides methods to create the OAuth signature and construct the Authorization header.
 *
 * Example usage:
 * ```php
 * $signer = new OAuthSigner($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
 * $authHeader = $signer->createAuthHeader('GET', 'https://api.example.com/resource', ['param1' => 'value1']);
 * ```
 */
class OAuthSigner
{
    public function __construct
    (
        string $consumerKey ,
        string $consumerSecret ,
        string $accessToken,
        string $accessTokenSecret
    )
    {
        $this->consumerKey       = $consumerKey;
        $this->consumerSecret    = $consumerSecret;
        $this->accessToken       = $accessToken;
        $this->accessTokenSecret = $accessTokenSecret;
    }

    /**
     * The default version of the OAuth signature.
     */
    public const string VERSION = '1.0' ;

    /**
     * OAuth access token for the user.
     * @var string
     */
    public string $accessToken ;

    /**
     * OAuth access token secret for the user.
     * @var string
     */
    public string $accessTokenSecret ;

    /**
     * OAuth consumer key provided by the service.
     * @var string
     */
    public string $consumerKey ;

    /**
     * OAuth consumer secret provided by the service.
     * @var string
     */
    public string $consumerSecret ;

    /**
     * @throws RandomException
     */
    public function createAuthHeader( string $method , string $url , array $parameters = [] ): string
    {
        $oauthParams = $this->generateSignature( $method , $url , $parameters ) ;
        $headerParts = [];

        foreach ( $oauthParams as $key => $value )
        {
            $headerParts[] = rawurlencode( $key ) . '="' . rawurlencode( $value ) . '"' ;
        }

        return AuthScheme::prefix( AuthScheme::OAUTH ) . implode( ', ' , $headerParts ) ;
    }

    /**
     * @throws RandomException
     */
    public function generateSignature( string $method , string $url , array $parameters = [] ) :array
    {
        $oauthParams =
        [
            OAuthParameters::OAUTH_CONSUMER_KEY     => $this->consumerKey ,
            OAuthParameters::OAUTH_TOKEN            => $this->accessToken ,
            OAuthParameters::OAUTH_SIGNATURE_METHOD => OAuthSignatureMethod::HMAC_SHA256 ,
            OAuthParameters::OAUTH_TIMESTAMP        => time() ,
            OAuthParameters::OAUTH_NONCE            => bin2hex( random_bytes(16 ) ) ,
            OAuthParameters::OAUTH_VERSION          => self::VERSION
        ];

        $allParams = array_merge( $oauthParams , $parameters ) ;

        ksort($allParams ) ;

        $paramString = http_build_query( $allParams, '' , '&' , PHP_QUERY_RFC3986 ) ;
        $baseString  = strtoupper( $method ) . '&' . rawurlencode( $url ) . '&' . rawurlencode( $paramString ) ;
        $signingKey  = rawurlencode( $this->consumerSecret ) . '&' . rawurlencode( $this->accessTokenSecret ) ;

        $oauthParams[ OAuthParameters::OAUTH_SIGNATURE ] = base64_encode( hash_hmac( HashAlgorithm::SHA256 , $baseString , $signingKey , true ) ) ;

        return $oauthParams;
    }
}