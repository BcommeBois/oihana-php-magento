<?php

namespace oihana\magento\schema;

use oihana\reflect\attributes\HydrateWith;

/**
 * Defines basic features of a bundle product and contains multiple BundleItems.
 * @see https://developer.adobe.com/commerce/webapi/graphql-api/index.html#definition-ProductImage
 */
class Product extends Thing
{
    /**
     * The relative canonical URL.
     * This value is returned only if the system setting 'Use Canonical Link Meta Tag For Products' is enabled.
     * @var string|null
     */
    public ?string $canonical_url ;

    /**
     * An array of media gallery objects.
     * @var array|null
     */
    #[HydrateWith( ProductImage::class , ProductVideo::class ) ]
    public ?array $media_gallery ;

    /**
     * An array of MediaGalleryEntry objects.
     * **Deprecated* : Use media_gallery instead.
     * @var array|null
     */
    #[HydrateWith( MediaGalleryEntry::class ) ]
    public ?array $media_gallery_entries ;

    /**
     * A number or code assigned to a product to identify the product, options, price, and manufacturer.
     * @var string|null
     */
    public ?string $sku ;

    /**
     * The unique ID for a ProductInterface object.
     * @var int|string|null
     */
    public int|string|null $uid ;
}