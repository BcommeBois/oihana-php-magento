<?php

namespace oihana\magento\schema;

/**
 * Contains basic information about a product image or video.
 * @see https://developer.adobe.com/commerce/webapi/graphql-api/index.html#definition-MediaGalleryInterface
 * @example
 * ```json
 * {
 *     "disabled": false,
 *     "label": "xyz789",
 *     "position": 123,
 *     "url": "xyz789"
 * }
 * ```
 */
class MediaGalleryInterface extends Thing
{
    /**
     * Indicates whether the image is hidden from view.
     */
    public ?bool $disabled ;

    /**
     * The label of the product image or video.
     */
    public ?string $label ;

    /**
     * The media item's position after it has been sorted.
     */
    public ?int $position ;

    /**
     * The URL of the product image or video.
     */
    public ?string $url ;
}