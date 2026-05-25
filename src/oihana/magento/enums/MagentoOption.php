<?php

namespace oihana\magento\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The trait to defines the constants of the Magento options.
 */
class MagentoOption
{
    use ConstantsTrait ;

    /**
     * The 'headers' parameter.
     */
    public const string HEADERS = 'headers' ;

    /**
     * The 'json' parameter.
     */
    public const string JSON = 'json' ;

    /**
     * The 'query' parameter.
     */
    public const string QUERY = 'query' ;
}