<?php

namespace oihana\magento\enums ;

use oihana\reflect\traits\ConstantsTrait;

class Magento
{
    use ConstantsTrait ;

    public const string MAGENTO = 'magento' ;

    // ------ Settings

    public const string MAX_RETRIES = 'maxRetries' ;
    public const string MEDIA_PATH  = 'mediaPath'  ;
    public const string PATH        = 'path'  ;

    // ------ Client

    public const string AUTH     = 'auth'     ;
    public const string OAUTH    = 'oauth'    ;
    public const string BASE_URI = 'base_uri' ;
    public const string HANDLER  = 'handler'  ;
    public const string HEADERS  = 'headers'  ;
    public const string TIMEOUT  = 'timeout'    ;
    public const string VERIFY   = 'verify'    ;

    // ------ Oauth1

    public const string CONSUMER_KEY     = 'consumer_key' ;
    public const string CONSUMER_SECRET  = 'consumer_secret' ;
    public const string SIGNATURE_METHOD = 'signature_method' ;
    public const string TOKEN            = 'token' ;
    public const string TOKEN_SECRET     = 'token_secret' ;
}