<?php

namespace oihana\magento\schema;

use DateTime;
use JsonSerializable;
use ReflectionException;

use oihana\reflect\traits\ReflectionTrait;

class Thing implements JsonSerializable
{
    /**
     * Constructor to hydrate public properties from an array or stdClass.
     *
     * This allows objects to be quickly populated with associative data
     * without manually setting each property.
     *
     * @param array|object|null $init A data array or object used to initialize the instance.
     *                                Keys must match public property names.
     *
     * @throws ReflectionException
     */
    public function __construct( array|object|null $init = null )
    {
        if( isset( $init ) )
        {
            $init = (array) $init ;

            $publicProperties = [];
            $properties       = $this->getPublicProperties( $this );

            foreach ( $properties as $property )
            {
                $publicProperties[ $property->getName() ] = true ;
            }

            foreach ( $init as $key => $value )
            {
                if ( isset( $publicProperties[ $key ] ) )
                {
                    $this->{ $key } = $value;
                }
            }
        }
    }

    use ReflectionTrait ;

    /**
     * The unique identifier of the item.
     */
    public null|int|string $id  ;

    /**
     * The name of the item.
     * @var int|string|null
     */
    public int|string|null $name ;

    /**
     * Date of creation of the resource.
     */
    public null|string|DateTime $created_at ;

    /**
     * Date on which the resource was changed.
     */
    public null|string|DateTime $updated_at ;

    /**
     * Serializes the current object into a JSON array.
     * @throws ReflectionException If reflection fails when accessing properties.
     *
     */
    public function jsonSerialize() : array
    {
        return $this->toArray() ;
    }
}