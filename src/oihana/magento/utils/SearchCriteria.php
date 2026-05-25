<?php

namespace oihana\magento\utils;

use oihana\enums\Order;
use oihana\magento\enums\SearchCriteriaParam;

/**
 * Utility class to build search criteria arrays for Magento API.
 *
 * It allows you to define paging, filter groups, and sort orders,
 * and automatically flattens the array into the format expected by Magento.
 *
 * Example usage:
 *
 * 1. Simple initialization with defaults:
 * ```php
 * $criteria = new SearchCriteria();
 * print_r($criteria->get());
 * // [
 * //     'searchCriteria[currentPage]' => 1,
 * //     'searchCriteria[pageSize]' => 20
 * // ]
 * ```
 *
 * 2. Initialize with custom values:
 * ```php
 * $criteria = new SearchCriteria([
 *     SearchCriteriaParam::CURRENT_PAGE => 2,
 *     SearchCriteriaParam::PAGE_SIZE => 50
 * ]);
 * print_r($criteria->get());
 * // [
 * //     'searchCriteria[currentPage]' => 2,
 * //     'searchCriteria[pageSize]' => 50
 * // ]
 * ```
 *
 * 3. Adding filter groups and sort orders:
 * ```php
 * $criteria = (new SearchCriteria())
 *     ->addFilterGroup([
 *         ['field'=>'status','value'=>'1','condition_type'=>'eq'],
 *         ['field'=>'type','value'=>'simple','condition_type'=>'eq']
 *     ])
 *     ->addSortOrder('created_at', Order::DESC)
 *     ->setCurrentPage(2)
 *     ->setPageSize(50);
 *
 * print_r($criteria->get());
 * // [
 * //     'searchCriteria[currentPage]' => 2,
 * //     'searchCriteria[pageSize]' => 50,
 * //     'searchCriteria[filterGroups][0][filters][0][field]' => 'status',
 * //     'searchCriteria[filterGroups][0][filters][0][value]' => '1',
 * //     'searchCriteria[filterGroups][0][filters][0][condition_type]' => 'eq',
 * //     'searchCriteria[filterGroups][0][filters][1][field]' => 'type',
 * //     'searchCriteria[filterGroups][0][filters][1][value]' => 'simple',
 * //     'searchCriteria[filterGroups][0][filters][1][condition_type]' => 'eq',
 * //     'searchCriteria[sortOrders][0][field]' => 'created_at',
 * //     'searchCriteria[sortOrders][0][direction]' => 'DESC'
 * // ]
 * ```
 *
 * 4. Resetting criteria:
 * ```php
 * $criteria->resetFilterGroups();
 * $criteria->resetSortOrders();
 * $criteria->resetPaging();
 * $criteria->reset(); // resets everything to defaults
 * ```
 *
 * 5. Re-initialize with new values:
 * ```php
 * $criteria->initialize([
 *     SearchCriteriaParam::CURRENT_PAGE => 1,
 *     SearchCriteriaParam::PAGE_SIZE => 25
 * ]);
 * ```
 *
 * @package oihana\magento\utils
 */
class SearchCriteria
{
    /**
     * Constructor
     * @param array $init Optional initial values
     */
    public function __construct( array $init = [] )
    {
        $this->initialize( $init ) ;
    }

    /**
     * Default prefix for Magento API query keys
     */
    public const string DEFAULT_PREFIX = 'searchCriteria' ;

    /**
     * Default current page
     */
    public const int DEFAULT_CURRENT_PAGE = 1 ;

    /**
     * Default page size
     */
    public const int DEFAULT_PAGE_SIZE = 20 ;

    /**
     * Add a filter group
     *
     * @param array $filters Each filter: ['field'=>..., 'value'=>..., 'condition_type'=>...]
     * @return $this
     */
    public function addFilterGroup( array $filters ) :static
    {
        $this->_criteria[ SearchCriteriaParam::FILTER_GROUPS ][] =
        [
            SearchCriteriaParam::FILTERS => $filters
        ] ;
        return $this;
    }

    /**
     * Add a sort order
     *
     * @param string $field
     * @param string $direction ASC|DESC
     * @return $this
     */
    public function addSortOrder( string $field , string $direction = Order::ASC ): static
    {
        $this->_criteria[ SearchCriteriaParam::SORT_ORDERS ][] =
        [
            SearchCriteriaParam::FIELD     => $field ,
            SearchCriteriaParam::DIRECTION => $direction
        ];
        return $this;
    }

    /**
     * Get the flattened array ready for Magento API
     *
     * @return array
     */
    public function get(): array
    {
        return $this->flatten( $this->_criteria , self::DEFAULT_PREFIX );
    }

    /**
     * Initialize or re-initialize the SearchCriteria
     * @param array $init Optional initial values
     * @return $this
     */
    public function initialize( array $init = [] ): static
    {
        $this->_criteria =
        [
            SearchCriteriaParam::CURRENT_PAGE  => $init[SearchCriteriaParam::CURRENT_PAGE]  ?? 1 ,
            SearchCriteriaParam::PAGE_SIZE     => $init[SearchCriteriaParam::PAGE_SIZE]     ?? 20 ,
            SearchCriteriaParam::FILTER_GROUPS => $init[SearchCriteriaParam::FILTER_GROUPS] ?? [] ,
            SearchCriteriaParam::SORT_ORDERS   => $init[SearchCriteriaParam::SORT_ORDERS]   ?? [] ,
        ];
        return $this;
    }

    /**
     * Reset all criteria to defaults.
     * @return $this
     */
    public function reset(): static
    {
        $this->initialize() ;
        return $this;
    }

    /**
     * Reset only filter groups.
     *
     * @return $this
     */
    public function resetFilterGroups(): static
    {
        $this->_criteria[ SearchCriteriaParam::FILTER_GROUPS  ] = [];
        return $this;
    }

    /**
     * Reset only sort orders.
     *
     * @return $this
     */
    public function resetSortOrders(): static
    {
        $this->_criteria[ SearchCriteriaParam::SORT_ORDERS ] = [];
        return $this;
    }

    /**
     * Reset only paging (currentPage and pageSize) to defaults.
     *
     * @return $this
     */
    public function resetPaging(): static
    {
        $this->_criteria[ SearchCriteriaParam::CURRENT_PAGE ] = self::DEFAULT_CURRENT_PAGE ;
        $this->_criteria[ SearchCriteriaParam::PAGE_SIZE    ] = self::DEFAULT_PAGE_SIZE ;
        return $this;
    }

    /**
     * Set the current page
     *
     * @param int $page
     * @return $this
     */
    public function setCurrentPage( int $page = 1 ) :static
    {
        $this->_criteria[ SearchCriteriaParam::CURRENT_PAGE ] = $page ;
        return $this;
    }

    /**
     * Set the page size
     *
     * @param int $size
     * @return $this
     */
    public function setPageSize( int $size ) :static
    {
        $this->_criteria[ SearchCriteriaParam::PAGE_SIZE ] = $size ;
        return $this;
    }

    /**
     * @var array
     */
    private array $_criteria = [];

    /**
     * Recursively flattens an associative array into Magento-style query keys.
     *
     * @param array $array
     * @param string $prefix
     * @return array
     */
    private function flatten( array $array , string $prefix ) :array
    {
        $result = [];
        foreach ($array as $key => $value)
        {
            $current_key = $prefix . '[' . $key . ']';
            if (is_array($value))
            {
                $result = array_merge($result, $this->flatten($value, $current_key));
            }
            else
            {
                $result[$current_key] = $value;
            }
        }
        return $result;
    }
}