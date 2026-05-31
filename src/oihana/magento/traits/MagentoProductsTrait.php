<?php

namespace oihana\magento\traits;

use InvalidArgumentException;
use oihana\magento\schema\constants\MagentoProp;
use ReflectionException;

use DateInvalidTimeZoneException;
use DateMalformedStringException;

use Random\RandomException;

use GuzzleHttp\Exception\GuzzleException;

use oihana\magento\enums\ConditionType;
use oihana\magento\enums\MagentoOption;
use oihana\magento\enums\MagentoParam;
use oihana\magento\enums\SearchCriteriaParam;
use oihana\magento\utils\Fields;
use oihana\magento\utils\SearchCriteria;

use oihana\enums\http\HttpMethod;
use oihana\exceptions\http\Error401;
use oihana\exceptions\http\Error404;

use function oihana\core\date\formatDateTime;
use function oihana\files\path\joinPaths;

trait MagentoProductsTrait
{
    use MagentoClientTrait ;

    /**
     * Retrieves a product by SKU.
     *
     * @param  string $sku      The unique sku identifier of the product
     * @param ?string $schema   The optional class to map the document.
     * @param  string $endpoint The endpoint of the magento product resource (default 'products')
     *
     * @return mixed
     *
     * @throws Error401
     * @throws Error404
     * @throws GuzzleException
     * @throws RandomException
     * @throws ReflectionException
     */
    public function getProduct
    (
        string  $sku ,
        ?string $schema   = null ,
        string  $endpoint = 'products'
    )
    : mixed
    {
        $document = $this->execute( joinPaths( $endpoint , urlencode( $sku ) ) ) ;

        if( is_string( $schema ) && is_array( $document ) )
        {
            $document = $this->hydrate( $document , $schema ) ;
        }

        return $document ;
    }

    /**
     * Retrieves a list of products.
     *
     * @param array $init Optional parameters keyed by {@see MagentoParam} constants:
     *                    - `searchCriteria` : a {@see SearchCriteria} instance or an array of criteria.
     *                    - `endpoint`       : the products resource endpoint (default `products`).
     *                    - `fields`         : a {@see Fields} instance or an array of field definitions.
     *                    - `schema`         : an optional class name used to hydrate each document.
     *
     * @return mixed The decoded product list, hydrated into `$schema` instances when provided.
     *
     * @throws Error401
     * @throws Error404
     * @throws GuzzleException
     * @throws RandomException
     * @throws ReflectionException
     */
    public function getProducts
    (
        array $init = []
    )
    : mixed
    {
        $criteria = $init[ MagentoParam::SEARCH_CRITERIA ] ?? null ;
        $endpoint = $init[ MagentoParam::ENDPOINT        ] ?? 'products' ;
        $fields   = $init[ MagentoParam::FIELDS          ] ?? null ;
        $schema   = $init[ MagentoParam::SCHEMA          ] ?? null ;
        $options  = [];

        if ( !( $criteria instanceof SearchCriteria ) )
        {
            $criteria = new SearchCriteria( is_array( $criteria ) ? $criteria : [] ) ;
        }

        $query = $criteria->get() ;

        if ( $fields instanceof Fields || ( is_array( $fields ) && !empty( $fields ) ) )
        {
            $query[ MagentoParam::FIELDS ] = (string) ( $fields instanceof Fields ? $fields : new Fields( $fields ) ) ;
        }

        $options[ MagentoOption::QUERY ] = $query ;

        $documents = $this->execute( $endpoint , HttpMethod::GET , $options );

        if( isset( $schema ) && is_array( $documents ) )
        {
            $documents = array_map( fn( $document ) => $this->hydrate( $document , $schema ) , $documents ) ;
        }

        return $documents ;
    }

    /**
     * Retrieves products created or updated since a given date.
     *
     * @param array $init = []
     *   - since  : Required. Non-empty date string (default format "Y-m-d H:i:s") used as the
     *              lower bound for `created_at` / `updated_at`.
     *   - format : Optional. The date format used to parse `since` (default "Y-m-d H:i:s").
     *   - schema : Optional. The class used to map the returned documents.
     *
     * @return array|null
     *
     * @throws InvalidArgumentException     If `since` is missing or not a non-empty string.
     * @throws Error401
     * @throws Error404
     * @throws GuzzleException
     * @throws RandomException
     * @throws ReflectionException
     * @throws DateInvalidTimeZoneException
     * @throws DateMalformedStringException
     */
    public function getProductsSince( array $init = [] ) : ?array
    {
        $since = $init[ MagentoParam::SINCE ] ?? null ;

        if ( !is_string( $since ) || trim( $since ) === '' )
        {
            throw new InvalidArgumentException
            (
                "getProductsSince() requires a non-empty 'since' date value."
            ) ;
        }

        $format = $init[ MagentoParam::FORMAT ] ?? 'Y-m-d H:i:s' ;
        $since  = formatDateTime( $since , format: $format ) ;

        // A single filter group with two filters : within a group Magento applies OR,
        // so this matches products created_at >= since OR updated_at >= since.
        // FILTER_GROUPS must be a direct key of searchCriteria (not an anonymous nested
        // array), otherwise SearchCriteria drops it and no date filter is applied.
        $init[ MagentoParam::SEARCH_CRITERIA ] =
        [
            ...( $init[ MagentoParam::SEARCH_CRITERIA ] ?? [] ) ,
            SearchCriteriaParam::FILTER_GROUPS =>
            [
                [
                    SearchCriteriaParam::FILTERS =>
                    [
                        [
                            SearchCriteriaParam::FIELD          => MagentoProp::CREATED_AT ,
                            SearchCriteriaParam::VALUE          => $since ,
                            SearchCriteriaParam::CONDITION_TYPE => ConditionType::GTEQ
                        ] ,
                        [
                            SearchCriteriaParam::FIELD          => MagentoProp::UPDATED_AT ,
                            SearchCriteriaParam::VALUE          => $since ,
                            SearchCriteriaParam::CONDITION_TYPE => ConditionType::GTEQ
                        ]
                    ]
                ]
            ]
        ];

        return $this->getProducts( $init ) ;
    }
}