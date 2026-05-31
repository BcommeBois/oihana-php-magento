<?php

namespace tests\oihana\magento\traits;

use DI\Container;

use PHPUnit\Framework\TestCase;

use UnexpectedValueException;

use oihana\magento\MagentoClient;
use oihana\magento\traits\HasMagentoClientTrait;

/**
 * Unit coverage for {@see \oihana\magento\traits\HasMagentoClientTrait}.
 */
class HasMagentoClientTraitTest extends TestCase
{
    /**
     * @return object A throwaway host object using the trait under test.
     */
    private function host() : object
    {
        return new class
        {
            use HasMagentoClientTrait ;
        } ;
    }

    /**
     * Regression guard : resolving an absent/unknown service must leave the
     * client unset instead of raising a TypeError (the property used to be
     * non-nullable, so assigning null was fatal).
     */
    public function testInitializeWithUnknownServiceLeavesClientUnset() : void
    {
        $host = $this->host() ;

        $host->initializeMagento( [] , new Container() ) ;

        $this->assertFalse( isset( $host->magento ) ) ;
    }

    /**
     * `assertMagento()` must throw while the client has not been resolved.
     */
    public function testAssertMagentoThrowsWhenNotInitialized() : void
    {
        $this->expectException( UnexpectedValueException::class ) ;

        $host = $this->host() ;
        $host->initializeMagento( [] , new Container() ) ;
        $host->assertMagento() ;
    }

    /**
     * A `MagentoClient` instance passed directly is stored as-is.
     */
    public function testInitializeStoresProvidedClientInstance() : void
    {
        $client = new MagentoClient( new Container() , [] ) ;

        $host = $this->host() ;
        $host->initializeMagento( [ 'magento' => $client ] ) ;

        $this->assertSame( $client , $host->magento ) ;
    }

    /**
     * A service name is resolved from the container.
     */
    public function testInitializeResolvesClientFromContainer() : void
    {
        $client    = new MagentoClient( new Container() , [] ) ;
        $container = new Container() ;
        $container->set( 'magento.client' , $client ) ;

        $host = $this->host() ;
        $host->initializeMagento( [ 'magento' => 'magento.client' ] , $container ) ;

        $this->assertSame( $client , $host->magento ) ;
    }
}
