<?php

namespace oihana\magento\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The trait to defines the constants of the Magento parameters.
 */
class MagentoParam
{
    use ConstantsTrait ;

    /**
     * The 'endpoint' parameter.
     */
    public const string ENDPOINT = 'endpoint' ;

    /**
     * The 'fields' parameter.
     */
    public const string FIELDS = 'fields' ;

    /**
     * The 'format' parameter.
     */
    public const string FORMAT = 'format' ;

    /**
     * The 'items' parameter.
     */
    public const string ITEMS = 'items' ;

    /**
     * The 'query' parameter.
     */
    public const string QUERY = 'query' ;

    /**
     * The 'schema' parameter.
     */
    public const string SCHEMA = 'schema' ;

    /**
     * The 'since' parameter.
     */
    public const string SINCE = 'since' ;

    /**
     * The 'searchCriteria' parameter.
     */
    public const string SEARCH_CRITERIA = 'searchCriteria' ;

    /**
     * The 'storeId' parameter.
     */
    public const string STORE_ID = 'storeId' ;

    /**
     * The 'total_count' parameter.
     */
    public const string TOTAL_COUNT = 'total_count' ;
}