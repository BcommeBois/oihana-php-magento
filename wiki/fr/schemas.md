# Schémas typés

Plutôt que de manipuler les réponses Magento sous forme de tableaux associatifs nus, `oihana/php-magento` fournit un ensemble d'**entités typées** sous [`src/oihana/magento/schema/`](../../src/oihana/magento/schema/). Ces classes hydratent les payloads JSON renvoyés par l'API en objets PHP avec des propriétés nommées, et délèguent l'hydratation à [`oihana/php-reflect`](https://github.com/BcommeBois/oihana-php-reflect) via l'attribut `HydrateWith`.

## Catalogue

| Entité | Représente | Page Magento |
|---|---|---|
| [`Thing`](../../src/oihana/magento/schema/Thing.php) | Racine de la hiérarchie — id + name |  |
| [`Product`](../../src/oihana/magento/schema/Product.php) | Un produit | `GET /products/{sku}` |
| [`ProductImage`](../../src/oihana/magento/schema/ProductImage.php) | Image d'un produit (URL + rôle + position) |  |
| [`ProductVideo`](../../src/oihana/magento/schema/ProductVideo.php) | Vidéo d'un produit |  |
| [`MediaGalleryEntry`](../../src/oihana/magento/schema/MediaGalleryEntry.php) | Une entrée de la galerie média (image ou vidéo) | `GET /products/{sku}/media` |
| [`MediaGalleryInterface`](../../src/oihana/magento/schema/MediaGalleryInterface.php) | Interface décrivant une galerie complète |  |
| [`ProductMediaGalleryEntriesContent`](../../src/oihana/magento/schema/ProductMediaGalleryEntriesContent.php) | Le sous-objet `content` (base64 + mime + name) d'une entry image |  |
| [`ProductMediaGalleryEntriesVideoContent`](../../src/oihana/magento/schema/ProductMediaGalleryEntriesVideoContent.php) | Le sous-objet `video_content` (provider + url + title) d'une entry vidéo |  |

## Hydratation automatique

`MagentoClient::getProduct( $sku )` retourne un tableau associatif brut par défaut. Pour hydrater en `Product`, utiliser le helper `ReflectionTrait::hydrate()` exposé par le client :

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

Les propriétés annotées `HydrateWith` (par exemple `media_gallery_entries: array<MediaGalleryEntry>`) sont hydratées récursivement.

## Constantes de schéma

Pour éviter les magic strings (`'sku'`, `'media_gallery_entries'`, …), deux familles de constantes :

| Classe | Contenu | Composé via trait |
|---|---|---|
| [`MagentoProp`](../../src/oihana/magento/schema/constants/MagentoProp.php) | Nom de toutes les propriétés Magento utilisées par les entités | `ProductTrait`, `ThingTrait`, `MediaGalleryEntryTrait`, `MediaGalleryInterfaceTrait` |
| [`MagentoImageRole`](../../src/oihana/magento/schema/constants/MagentoImageRole.php) | Rôles d'image Magento (`image`, `small_image`, `thumbnail`, `swatch_image`) |  |

Exemple d'utilisation :

```php
use oihana\magento\schema\constants\MagentoProp ;
use oihana\magento\schema\constants\MagentoImageRole ;

if ( in_array( MagentoImageRole::THUMBNAIL , $entry->types , true ) )
{
    echo "Cette entry est la miniature" . PHP_EOL ;
}
```

## Enums liés

| Enum | Valeurs |
|---|---|
| [`MediaType`](../../src/oihana/magento/schema/enums/MediaType.php) | `image`, `external-video` — type d'une `MediaGalleryEntry` |
| [`ProductImageThumbnail`](../../src/oihana/magento/schema/enums/ProductImageThumbnail.php) | Tailles de miniature standard |

## Persistance — exemple ArangoDB

`oihana/php-magento` ne persiste rien par lui-même. Mais comme les entités sont des classes PHP standard, on peut les passer à `oihana/php-arango` pour les stocker en base documentaire :

```php
use oihana\arango\models\Documents ;
use oihana\magento\schema\Product ;

$products = new Documents( $container , [ /* ... */ ] ) ;

$raw       = $client->getProduct( 'SKU-12345' ) ;
$product   = $client->hydrate( $raw , Product::class ) ;
$persisted = $products->upsert( (array) $product ) ;
```

C'est le pattern typique d'une commande `magento:harvest:products` côté application hôte.

## Voir aussi

- [Démarrage rapide](getting-started.md) — installation et premier appel.
- [SearchCriteria](search-criteria.md) — construction des filtres + pagination.
- [oihana/php-reflect](https://github.com/BcommeBois/oihana-php-reflect) — couche d'hydratation utilisée par `ReflectionTrait::hydrate()`.
- [oihana/php-arango](https://github.com/BcommeBois/oihana-php-arango) — persistance ArangoDB.
