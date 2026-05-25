# Typed schemas

Rather than juggling Magento responses as bare associative arrays, `oihana/php-magento` ships a set of **typed entities** under [`src/oihana/magento/schema/`](../../src/oihana/magento/schema/). These classes hydrate the JSON payloads returned by the API into PHP objects with named properties, and delegate hydration to [`oihana/php-reflect`](https://github.com/BcommeBois/oihana-php-reflect) through the `HydrateWith` attribute.

## Catalogue

| Entity | Represents | Magento page |
|---|---|---|
| [`Thing`](../../src/oihana/magento/schema/Thing.php) | Root of the hierarchy — id + name |  |
| [`Product`](../../src/oihana/magento/schema/Product.php) | A product | `GET /products/{sku}` |
| [`ProductImage`](../../src/oihana/magento/schema/ProductImage.php) | A product image (URL + role + position) |  |
| [`ProductVideo`](../../src/oihana/magento/schema/ProductVideo.php) | A product video |  |
| [`MediaGalleryEntry`](../../src/oihana/magento/schema/MediaGalleryEntry.php) | A media gallery entry (image or video) | `GET /products/{sku}/media` |
| [`MediaGalleryInterface`](../../src/oihana/magento/schema/MediaGalleryInterface.php) | Interface describing a full gallery |  |
| [`ProductMediaGalleryEntriesContent`](../../src/oihana/magento/schema/ProductMediaGalleryEntriesContent.php) | The `content` sub-object (base64 + mime + name) of an image entry |  |
| [`ProductMediaGalleryEntriesVideoContent`](../../src/oihana/magento/schema/ProductMediaGalleryEntriesVideoContent.php) | The `video_content` sub-object (provider + url + title) of a video entry |  |

## Automatic hydration

`MagentoClient::getProduct( $sku )` returns a raw associative array by default. To hydrate into a `Product`, use the `ReflectionTrait::hydrate()` helper exposed by the client:

```php
use oihana\magento\schema\Product ;

$raw = $client->getProduct( 'SKU-12345' ) ;
/** @var Product $product */
$product = $client->hydrate( $raw , Product::class ) ;

echo $product->name        . PHP_EOL ;
echo $product->sku         . PHP_EOL ;
echo $product->price       . PHP_EOL ;
echo $product->type_id     . PHP_EOL ;

foreach ( $product->media_gallery_entries as $entry )
{
    /** @var MediaGalleryEntry $entry */
    echo $entry->file . ' — ' . $entry->media_type . PHP_EOL ;
}
```

Properties annotated with `HydrateWith` (for example `media_gallery_entries: array<MediaGalleryEntry>`) are hydrated recursively.

## Schema constants

To avoid magic strings (`'sku'`, `'media_gallery_entries'`, …), two families of constants:

| Class | Content | Composed through trait |
|---|---|---|
| [`MagentoProp`](../../src/oihana/magento/schema/constants/MagentoProp.php) | Name of every Magento property used by the entities | `ProductTrait`, `ThingTrait`, `MediaGalleryEntryTrait`, `MediaGalleryInterfaceTrait` |
| [`MagentoImageRole`](../../src/oihana/magento/schema/constants/MagentoImageRole.php) | Magento image roles (`image`, `small_image`, `thumbnail`, `swatch_image`) |  |

Example use:

```php
use oihana\magento\schema\constants\MagentoProp ;
use oihana\magento\schema\constants\MagentoImageRole ;

if ( in_array( MagentoImageRole::THUMBNAIL , $entry->types , true ) )
{
    echo "This entry is the thumbnail" . PHP_EOL ;
}
```

## Related enums

| Enum | Values |
|---|---|
| [`MediaType`](../../src/oihana/magento/schema/enums/MediaType.php) | `image`, `external-video` — type of a `MediaGalleryEntry` |
| [`ProductImageThumbnail`](../../src/oihana/magento/schema/enums/ProductImageThumbnail.php) | Standard thumbnail sizes |

## See also

- [Getting started](getting-started.md) — installation and first call.
- [SearchCriteria](search-criteria.md) — building filters + pagination.
- [oihana/php-reflect](https://github.com/BcommeBois/oihana-php-reflect) — hydration layer used by `ReflectionTrait::hydrate()`.
