<?php

namespace oihana\magento\schema\enums;

use oihana\magento\schema\MediaGalleryEntry;
use oihana\reflect\traits\ConstantsTrait;

/**
 * The enumeration of the image or video media types.
 * @see MediaGalleryEntry
 */
class MediaType
{
    use ConstantsTrait ;

    /**
     * The 'image' type.
     */
    public const string IMAGE = 'image' ;

    /**
     * The 'video' type.
     */
    public const string VIDEO = 'video' ;
}