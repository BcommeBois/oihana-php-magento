<?php

namespace tests\oihana\magento\schema;

use PHPUnit\Framework\TestCase;

use oihana\magento\schema\Product;
use oihana\magento\schema\Thing;

/**
 * Unit coverage for {@see \oihana\magento\schema\Thing} and its hydration
 * behaviour (shared by every schema entity through inheritance).
 *
 * Note: the constructor performs a flat assignment of matching public
 * properties only. Deep hydration of nested entities (the `#[HydrateWith]`
 * attribute, e.g. {@see Product::$media_gallery}) is handled by
 * `ReflectionTrait::hydrate()`, not by the constructor — see
 * {@see \tests\oihana\magento\traits\MagentoProductsTraitTest}.
 */
class ThingTest extends TestCase
{
    public function testConstructorHydratesKnownProperties() : void
    {
        $thing = new Thing( [ 'id' => 1 , 'name' => 'hello' ] ) ;

        $this->assertSame( 1 , $thing->id ) ;
        $this->assertSame( 'hello' , $thing->name ) ;
    }

    public function testConstructorIgnoresUnknownKeys() : void
    {
        $thing = new Thing( [ 'id' => 1 , 'unknown' => 'ignored' ] ) ;

        $this->assertSame( 1 , $thing->id ) ;
        $this->assertFalse( property_exists( $thing , 'unknown' ) ) ;
    }

    public function testConstructorAcceptsObjectInput() : void
    {
        $thing = new Thing( (object) [ 'id' => 9 ] ) ;

        $this->assertSame( 9 , $thing->id ) ;
    }

    public function testJsonSerializeReturnsOnlyInitialisedProperties() : void
    {
        $thing = new Thing( [ 'id' => 1 , 'name' => 'n' ] ) ;

        $this->assertSame( [ 'id' => 1 , 'name' => 'n' ] , $thing->jsonSerialize() ) ;
    }

    public function testJsonSerializeOfEmptyThingIsEmptyArray() : void
    {
        $this->assertSame( [] , ( new Thing( null ) )->jsonSerialize() ) ;
    }

    public function testProductInheritsThingProperties() : void
    {
        $product = new Product( [ 'id' => 2 , 'name' => 'n' , 'sku' => 'ABC' , 'uid' => '7' ] ) ;

        $this->assertSame( 2 , $product->id ) ;
        $this->assertSame( 'n' , $product->name ) ;
        $this->assertSame( 'ABC' , $product->sku ) ;
        $this->assertSame( '7' , $product->uid ) ;
    }
}
