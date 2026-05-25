<?php

namespace oihana\magento\schema;

/**
 * Contains an image in base64 format and basic information about the image.
 * @see https://developer.adobe.com/commerce/webapi/graphql-api/index.html#definition-ProductMediaGalleryEntriesContent
 * @example
 * ```json
 * {
 *    "base64_encoded_data": "xyz789",
 *    "name": "xyz789",
 *    "type": "abc123"
 * }
 * ```
 */
class ProductMediaGalleryEntriesContent extends Thing
{
    /**
     * The image in base64 format.
     */
    public ?string $base64_encoded_data ;

    /**
     * The MIME type of the file, such as image/png.
     */
    public ?string $type ;
}