<?php

namespace oihana\magento\schema;

/**
 * Defines characteristics about images and videos associated with a specific product.
 * @see https://developer.adobe.com/commerce/webapi/graphql-api/index.html#definition-MediaGalleryEntry
 * @example
 * ```json
 * {
 *     "content": ProductMediaGalleryEntriesContent,
 *     "disabled": true,
 *     "file": "abc123",
 *     "id": 987,
 *     "label": "xyz789",
 *     "media_type": "xyz789",
 *     "position": 123,
 *     "types": ["abc123"],
 *     "uid": 4,
 *     "video_content": ProductMediaGalleryEntriesVideoContent
 * }
 * ```
 */
class MediaGalleryEntry extends Thing
{
    /**
     * Indicates whether the image is hidden from view.
     */
    public ?ProductMediaGalleryEntriesContent $content ;

    /**
     * Indicates whether the image is hidden from view.
     */
    public ?bool $disabled ;

    /**
     * The path of the image on the server.
     */
    public ?string $file  ;

    /**
     * The alt text displayed on the storefront when the user points to the image.
     */
    public ?string $label ;

    /**
     * Either `image` or `video`.
     */
    public ?string $media_type ;

    /**
     * The media item's position after it has been sorted.
     */
    public ?string $position ;

    /**
     * Array of image types. It can have the following values: image, small_image, thumbnail.
     */
    public ?array $types ;

    /**
     * The unique ID for a MediaGalleryEntry object.
     */
    public int|string|null $uid ;

    /**
     * Details about the content of a video item.
     */
    public ?ProductMediaGalleryEntriesVideoContent $video_content ;
}