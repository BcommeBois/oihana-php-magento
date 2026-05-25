<?php

namespace oihana\magento\traits;

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
     * @param array $init {
     * @return mixed
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

        if ( is_array( $criteria ) )
        {
            $criteria = new SearchCriteria($criteria);
        }

        $criteria = $criteria instanceof SearchCriteria ? $criteria : new SearchCriteria() ;

        $query = $criteria instanceof SearchCriteria ? $criteria->get() : $criteria ;

        if ( $fields instanceof Fields || (is_array($fields) && !empty($fields)))
        {
            $query[ MagentoParam::FIELDS ] = (string) ($fields instanceof Fields ? $fields : new Fields($fields));
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
     *   - since   : Date in format "Y-m-d H:i:s" - The method try to format the date.
     *   - schema : The optional class to map the documents.
     *
     * @return array|null
     *
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
        $format = $init[ MagentoParam::FORMAT ] ?? 'Y-m-d H:i:s'  ;
        $since  = formatDateTime( $init[ MagentoParam::SINCE ] , format:$format ) ;

        $init[ MagentoParam::SEARCH_CRITERIA ] =
        [
            ...( $init[ MagentoParam::SEARCH_CRITERIA ] ?? [] ) ,
            [
                SearchCriteriaParam::FILTER_GROUPS =>
                [
                    [
                        SearchCriteriaParam::FILTERS =>
                        [
                            [
                                SearchCriteriaParam::FIELD          => 'created_at' ,
                                SearchCriteriaParam::VALUE          => $since ,
                                SearchCriteriaParam::CONDITION_TYPE => ConditionType::GTEQ
                            ]
                        ]
                    ], // OR
                    [
                        SearchCriteriaParam::FILTERS =>
                        [
                            [
                                SearchCriteriaParam::FIELD          => 'updated_at' ,
                                SearchCriteriaParam::VALUE          => $since,
                                SearchCriteriaParam::CONDITION_TYPE => ConditionType::GTEQ
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $this->getProducts( $init ) ;
    }
}