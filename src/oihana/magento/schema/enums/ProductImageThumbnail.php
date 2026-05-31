<?php

namespace oihana\magento\schema\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The enumeration of the image thumbnail types.
 * @see https://developer.adobe.com/commerce/webapi/graphql-api/index.html#definition-ProductImageThumbnail
 */
class ProductImageThumbnail
{
    use ConstantsTrait ;

    /**
     * Use thumbnail of product as image.
     */
    public const string ITSELF = 'ITSELF' ;

    /**
     * Use thumbnail of product's parent as image.
     */
    public const string PARENT = 'PARENT' ;
}