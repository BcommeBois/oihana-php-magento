<?php

namespace oihana\magento\schema;

/**
 * Contains a link to a video file and basic information about the video.
 * @see https://developer.adobe.com/commerce/webapi/graphql-api/index.html#definition-ProductMediaGalleryEntriesVideoContent
 * @example
 * ```json
 * {
 *     "media_type": "xyz789",
 *     "video_description": "xyz789",
 *     "video_metadata": "xyz789",
 *     "video_provider": "xyz789",
 *     "video_title": "abc123",
 *     "video_url": "abc123"
 * }
 * ```
 */
class ProductMediaGalleryEntriesVideoContent extends Thing
{
    /**
     * Must be external-video.
     */
    public ?string $media_type ;

    /**
     * A description of the video.
     */
    public ?string $video_description ;

    /**
     * Optional data about the video.
     */
    public ?string $video_metadata ;

    /**
     * Describes the video source.
     */
    public ?string $video_provider ;

    /**
     * The title of the video.
     */
    public ?string $video_title ;

    /**
     * The URL to the video.
     */
    public ?string $video_url ;
}