<?php

namespace oihana\magento\schema;

/**
 * Contains product image information, including the image URL and label.
 * @see https://developer.adobe.com/commerce/webapi/graphql-api/index.html#definition-ProductImage
 * @example
 * ```json
 * {
 *     "disabled": false,
 *     "label": "abc123",
 *     "position": 987,
 *     "url": "abc123"
 * }
 * ```
 */
class ProductImage extends MediaGalleryInterface
{

}