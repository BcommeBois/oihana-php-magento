<?php

namespace oihana\magento\utils;

/**
 * Utility class to build Magento "fields" query strings from arrays.
 *
 * This class allows you to define which fields to retrieve from Magento REST API,
 * including nested fields, and automatically generates the query string.
 *
 * Example usage:
 *
 * 1. Using a raw string:
 * ```php
 * $fields = new Fields('items[sku,name],total_count');
 * echo (string)$fields; // "items[sku,name],total_count"
 * ```
 *
 * 2. Using a simple array:
 * ```php
 * $fields = new Fields([
 *     'items' => ['sku','name','price'],
 *     'total_count'
 * ]);
 * echo (string)$fields; // "items[sku,name,price],total_count"
 * ```
 *
 * 3. Using a nested array for sub-fields:
 * ```php
 * $fields = new Fields([
 *     'items' => [
 *         'sku',
 *         'name',
 *         'custom_attributes' => ['attribute_code','value'],
 *         'extension_attributes' => [
 *             'stock_item' => ['qty','is_in_stock']
 *         ]
 *     ],
 *     'total_count'
 * ]);
 * echo (string)$fields;
 * // "items[sku,name,custom_attributes[attr_code,value],extension_attributes[stock_item[qty,is_in_stock]]],total_count"
 * ```
 *
 * @package oihana\magento\utils
 */
class Fields
{
    /**
     * Creates a new Fields instance.
     *
     * @param string|array|null $fields The fields definition.
     *   - string: raw Magento fields query string
     *   - array: structured array representing fields and nested fields
     *   - null: initializes an empty fields definition
     */
    public function __construct( string|array|null $fields = null )
    {
        $this->fields = $fields ;
    }

    /**
     * The fields definition array.
     *
     * You can assign a string or an array:
     *
     * ```php
     * $fields->fields = 'items[sku,name],total_count';
     * $fields->fields = ['items' => ['sku','name']];
     * ```
     *
     * @var array
     */
    public array $fields
    {
        get => $this->_fields ;

        set( null|array|string $value )
        {
            if ( is_string( $value ) )
            {
                $this->_fields = [ self::RAW => $value ] ;
            }
            else if ( is_array( $value ) )
            {
                $this->_fields = $value ;
            }
            else
            {
                $this->_fields = [];
            }
        }
    }

    /**
     * Get the query string explicitly.
     * @return string
     */
    public function get(): string
    {
        return (string) $this ;
    }

    /**
     * Return the query string (for magic __toString).
     *
     * @return string
     */
    public function __toString(): string
    {
        if (isset( $this->_fields[ self::RAW ] ) )
        {
            return $this->_fields[ self::RAW ] ;
        }
        return $this->build( $this->_fields ) ;
    }

    /**
     * The internal 'raw' parameter used to store a string directly.
     */
    protected const string RAW = '__raw' ;

    /**
     * Internal representation as array
     */
    private array $_fields = [];

    /**
     * Recursively build the fields query string from an array.
     *
     * Converts a structured array of fields (with optional nested sub-fields)
     * into a Magento-compatible query string.
     *
     * Examples:
     *
     * 1. Simple fields:
     * ```php
     * $fields = ['sku', 'name'];
     * $query = $this->build($fields);
     * // "sku,name"
     * ```
     *
     * 2. Nested fields:
     * ```php
     * $fields = ['items' => ['sku','name','price']];
     * $query = $this->build($fields);
     * // "items[sku,name,price]"
     * ```
     *
     * 3. Multiple nested levels:
     * ```php
     * $fields = [
     * 'items' => [
     * 'sku',
     * 'extension_attributes' => [
     * 'stock_item' => ['qty','is_in_stock']
     * ]
     * ]
     * ];
     * $query = $this->build($fields);
     * // "items[sku,extension_attributes[stock_item[qty,is_in_stock]]]"
     * ```
     *
     * 4. Empty array returns empty string:
     * ```php
     * $fields = [];
     * $query = $this->build($fields);
     * // ""
     * ```
     *
     * @param array $fields
     * @return string
     */
    protected function build( array $fields ) :string
    {
        if ( empty( $fields ) )
        {
            return '' ;
        }
        $parts = [];
        foreach ( $fields as $key => $value )
        {
            if ( is_array( $value ) )
            {
                $key     = is_int( $key ) ? $value : $key ;
                $parts[] = $key . '[' . $this->build( $value ) . ']' ;
            }
            else
            {
                $parts[] = $value;
            }
        }
        return implode(',', $parts);
    }
}