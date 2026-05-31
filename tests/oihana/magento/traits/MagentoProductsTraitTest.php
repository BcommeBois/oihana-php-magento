<?php

namespace tests\oihana\magento\traits;

use DI\Container;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

use InvalidArgumentException;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\RequestInterface;

use oihana\magento\MagentoClient;
use oihana\magento\enums\Magento;
use oihana\magento\enums\MagentoParam;
use oihana\magento\enums\SearchCriteriaParam;
use oihana\magento\schema\Product;
use oihana\magento\utils\Fields;
use oihana\magento\utils\SearchCriteria;

/**
 * Unit coverage for {@see \oihana\magento\traits\MagentoProductsTrait}.
 *
 * As for {@see MagentoClientTraitTest}, the transport is a Guzzle
 * {@see MockHandler} and a history middleware records the outgoing requests,
 * so we can assert how `getProduct()` / `getProducts()` build their URL,
 * query string and how they decode the response.
 */
class MagentoProductsTraitTest extends TestCase
{
    private const string BASE_URI = 'https://shop.example.com/rest/V1/' ;

    /**
     * @var array<int,array>
     */
    private array $history = [] ;

    /**
     * @param Response[] $responses
     */
    private function makeClient( array $responses , array $init = [] ) : MagentoClient
    {
        $this->history = [] ;

        $stack = HandlerStack::create( new MockHandler( $responses ) ) ;
        $stack->push( Middleware::history( $this->history ) ) ;

        return new MagentoClient
        (
            new Container() ,
            [
                Magento::BASE_URI => self::BASE_URI ,
                Magento::HANDLER  => $stack ,
                ...$init ,
            ]
        ) ;
    }

    private function lastRequest() : RequestInterface
    {
        return $this->history[ array_key_last( $this->history ) ][ 'request' ] ;
    }

    /**
     * `getProduct()` targets `{endpoint}/{sku}` and decodes the response.
     */
    public function testGetProductFetchesBySku() : void
    {
        $client = $this->makeClient( [ new Response( 200 , [] , '{"sku":"ABC"}' ) ] ) ;

        $result = $client->getProduct( 'ABC' ) ;

        $this->assertStringEndsWith( '/rest/V1/products/ABC' , $this->lastRequest()->getUri()->getPath() ) ;
        $this->assertSame( 'ABC' , $result[ 'sku' ] ) ;
    }

    /**
     * `getProducts()` defaults to the `products` endpoint and a default
     * search criteria (page 1, size 20).
     */
    public function testGetProductsUsesDefaultSearchCriteria() : void
    {
        $client = $this->makeClient( [ new Response( 200 , [] , '{"items":[],"total_count":0}' ) ] ) ;

        $client->getProducts() ;

        $query = urldecode( $this->lastRequest()->getUri()->getQuery() ) ;

        $this->assertStringEndsWith( '/rest/V1/products' , $this->lastRequest()->getUri()->getPath() ) ;
        $this->assertStringContainsString( 'searchCriteria[currentPage]=1' , $query ) ;
        $this->assertStringContainsString( 'searchCriteria[pageSize]=20' , $query ) ;
    }

    /**
     * An array `searchCriteria` is normalised into a {@see SearchCriteria}
     * and flattened into the Magento query format.
     */
    public function testGetProductsAcceptsSearchCriteriaArray() : void
    {
        $client = $this->makeClient( [ new Response( 200 , [] , '{"items":[]}' ) ] ) ;

        $client->getProducts
        ([
            MagentoParam::SEARCH_CRITERIA =>
            [
                SearchCriteriaParam::CURRENT_PAGE => 2 ,
                SearchCriteriaParam::PAGE_SIZE    => 50 ,
            ]
        ]) ;

        $query = urldecode( $this->lastRequest()->getUri()->getQuery() ) ;

        $this->assertStringContainsString( 'searchCriteria[currentPage]=2' , $query ) ;
        $this->assertStringContainsString( 'searchCriteria[pageSize]=50' , $query ) ;
    }

    /**
     * A {@see SearchCriteria} instance is accepted as-is.
     */
    public function testGetProductsAcceptsSearchCriteriaInstance() : void
    {
        $criteria = ( new SearchCriteria() )->setCurrentPage( 3 ) ;

        $client = $this->makeClient( [ new Response( 200 , [] , '{"items":[]}' ) ] ) ;
        $client->getProducts( [ MagentoParam::SEARCH_CRITERIA => $criteria ] ) ;

        $query = urldecode( $this->lastRequest()->getUri()->getQuery() ) ;

        $this->assertStringContainsString( 'searchCriteria[currentPage]=3' , $query ) ;
    }

    /**
     * The `fields` parameter (array or {@see Fields}) is serialised into the
     * Magento `fields` query parameter.
     */
    public function testGetProductsAppendsFieldsQueryParameter() : void
    {
        $client = $this->makeClient( [ new Response( 200 , [] , '{"items":[]}' ) ] ) ;

        $client->getProducts
        ([
            MagentoParam::FIELDS => [ 'items' => [ 'sku' , 'name' ] , 'total_count' ] ,
        ]) ;

        $query = urldecode( $this->lastRequest()->getUri()->getQuery() ) ;

        $this->assertStringContainsString( 'fields=items[sku,name],total_count' , $query ) ;
    }

    /**
     * When a `schema` is provided, only the envelope's `items` are hydrated
     * into instances; the rest of the envelope (e.g. `total_count`) is left
     * untouched. Mapping over the whole envelope would feed `hydrate()` an
     * int and raise a TypeError.
     */
    public function testGetProductsHydratesOnlyItemsWhenSchemaProvided() : void
    {
        $client = $this->makeClient
        (
            [ new Response( 200 , [] , '{"items":[{"sku":"A"},{"sku":"B"}],"total_count":2}' ) ]
        ) ;

        $result = $client->getProducts( [ MagentoParam::SCHEMA => Product::class ] ) ;

        $this->assertContainsOnlyInstancesOf( Product::class , $result[ 'items' ] ) ;
        $this->assertSame( 'A' , $result[ 'items' ][ 0 ]->sku ) ;
        $this->assertSame( 2 , $result[ 'total_count' ] ) ;
    }

    /**
     * `getProducts()` returns the decoded response body.
     */
    public function testGetProductsReturnsDecodedBody() : void
    {
        $client = $this->makeClient( [ new Response( 200 , [] , '{"items":[{"sku":"A"}],"total_count":1}' ) ] ) ;

        $result = $client->getProducts() ;

        $this->assertSame( 1 , $result[ 'total_count' ] ) ;
        $this->assertSame( 'A' , $result[ 'items' ][ 0 ][ 'sku' ] ) ;
    }

    /**
     * `getProductsSince()` requires a `since` value : calling it without one
     * must fail fast with an {@see InvalidArgumentException} rather than
     * silently defaulting to "now".
     */
    public function testGetProductsSinceThrowsWhenSinceIsMissing() : void
    {
        $this->expectException( InvalidArgumentException::class ) ;

        $client = $this->makeClient( [ new Response( 200 , [] , '{"items":[]}' ) ] ) ;
        $client->getProductsSince() ;
    }

    /**
     * An empty `since` string is rejected as well.
     */
    public function testGetProductsSinceThrowsWhenSinceIsEmpty() : void
    {
        $this->expectException( InvalidArgumentException::class ) ;

        $client = $this->makeClient( [ new Response( 200 , [] , '{"items":[]}' ) ] ) ;
        $client->getProductsSince( [ MagentoParam::SINCE => '   ' ] ) ;
    }

    /**
     * With a valid `since`, the request carries the date filter as a single
     * filter group holding both `created_at` and `updated_at` (OR semantics),
     * using the `gteq` condition and the formatted date.
     */
    public function testGetProductsSinceBuildsCreatedAndUpdatedFilters() : void
    {
        $client = $this->makeClient( [ new Response( 200 , [] , '{"items":[]}' ) ] ) ;

        $client->getProductsSince( [ MagentoParam::SINCE => '2026-01-15 10:30:00' ] ) ;

        $query = urldecode( $this->lastRequest()->getUri()->getQuery() ) ;

        // Both fields live in the SAME group (index 0) => OR within the group.
        $this->assertStringContainsString( 'searchCriteria[filter_groups][0][filters][0][field]=created_at' , $query ) ;
        $this->assertStringContainsString( 'searchCriteria[filter_groups][0][filters][1][field]=updated_at' , $query ) ;
        $this->assertStringContainsString( '[condition_type]=gteq' , $query ) ;
        $this->assertStringContainsString( '2026-01-15 10:30:00' , $query ) ;
    }
}
