<?php

namespace oihana\magento\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The trait to defines the constants of the Magento searchCriteria parameters.
 * Useful for building queries programmatically without hardcoding strings.
 */
class SearchCriteriaParam
{
    use ConstantsTrait ;

    /**
     * Filter condition type, e.g. 'eq', 'neq', 'like', 'in', 'gteq', 'lteq'.
     */
    public const string CONDITION_TYPE = 'condition_type';

    /**
     * The 'currentPage' parameter.
     */
    public const string CURRENT_PAGE = 'currentPage' ;

    /**
     * Sort direction, 'ASC' or 'DESC'.
     */
    public const string DIRECTION = 'direction';

    /**
     * The 'filter_groups' array, containing groups of filters (AND / OR logic).
     */
    public const string FILTER_GROUPS = 'filter_groups';

    /**
     * The 'filters' array inside each filter group.
     */
    public const string FILTERS = 'filters';

    /**
     * Filter field name, e.g. 'sku', 'price', 'status'.
     */
    public const string FIELD = 'field';

    /**
     * The 'pageSize' parameter for pagination.
     */
    public const string PAGE_SIZE = 'pageSize';

    /**
     * The 'sortOrders' array for ordering results.
     */
    public const string SORT_ORDERS = 'sortOrders';

    /**
     * Filter value.
     */
    public const string VALUE = 'value';
}