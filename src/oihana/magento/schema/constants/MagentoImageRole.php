<?php

namespace oihana\magento\schema\constants;

use oihana\magento\schema\MediaGalleryEntry;
use oihana\reflect\traits\ConstantsTrait;

/**
 * The enumeration of Magento image roles.
 * @see MediaGalleryEntry
 */
class MagentoImageRole
{
    use ConstantsTrait ;

    /**
     * Main product image.
     */
    public const string IMAGE = 'image' ;

    /**
     * Small image (listing, category).
     */
    public const string SMALL_IMAGE = 'small_image' ;

    /**
     * Swatch image.
     */
    public const string SWATCH = 'swatch_image' ;

    /**
     * Thumbnail image.
     */
    public const string THUMBNAIL = 'thumbnail' ;
}