<?php

namespace oihana\magento\schema;

/**
 * Contains information about a product video.
 * @see https://developer.adobe.com/commerce/webapi/graphql-api/index.html#definition-ProductVideo
 * @example
 * ```json
 * {
 *     "disabled": true,
 *     "label": "abc123",
 *     "position": 123,
 *     "url": "xyz789",
 *     "video_content": ProductMediaGalleryEntriesVideoContent
 * }
 * ```
 */
class ProductVideo extends MediaGalleryInterface
{
    /**
     * Contains a ProductMediaGalleryEntriesVideoContent object.
     */
    public ?ProductMediaGalleryEntriesVideoContent $video_content ;
}