<?php

namespace oihana\magento\traits;

use UnexpectedValueException;

use DI\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use oihana\enums\Char;
use oihana\magento\MagentoClient;

/**
 * Provides a standardized way to manage a Magento client instance within a class.
 *
 * Responsibilities:
 * - Storing a reference to a `MagentoClient`.
 * - Asserting that the client has been initialized before use.
 * - Initializing the client from an array of parameters and an optional PSR-11 container.
 *
 * Usage example:
 * ```php
 * $this->initializeMagento(['magento' => 'magentoServiceName'], $container);
 * $this->assertMagento(); // ensures the client is ready
 * $this->magento->someMagentoMethod();
 * ```
 *
 * @package oihana\magento\traits
 */
trait HasMagentoClientTrait
{
    /**
     * The Magento client reference.
     * @var MagentoClient
     */
    public MagentoClient $magento ;

    /**
     * The 'magento' parameter key.
     */
    public const string MAGENTO = 'magento' ;

    /**
     * Asserts that the 'magento' client property has been initialized.
     * @return void
     * @throws UnexpectedValueException If the `magento` client is not set.
     */
    public function assertMagento():void
    {
        if( !isset( $this->magento ) )
        {
            throw new UnexpectedValueException( 'The `magento` client is not set.' ) ;
        }
    }

    /**
     * Initializes the 'magento' property.
     *
     * This method attempts to find a service name in the '$init' array and
     * uses the provided PSR container to retrieve the Client instance.
     *
     * @param array          $init      An array of initialization parameters, expected to contain the documents service name.
     * @param Container|null $container An optional PSR-11 container for service resolution.
     *
     * @return static The instance of the class using this method, for method chaining.
     *
     * @throws ContainerExceptionInterface If an error occurs while retrieving the entry from the container.
     * @throws NotFoundExceptionInterface  If no entry was found in the container for the given service name.
     */
    public function initializeMagento( array $init = [] , ?Container $container = null ) :static
    {
        $magento = $init[ self::MAGENTO ] ?? null ;
        if( is_string( $magento ) && $magento != Char::EMPTY && $container?->has( $magento ) )
        {
            $magento = $container->get( $magento ) ;
        }
        $this->magento = $magento instanceof MagentoClient ? $magento : null ;
        return $this ;
    }

}