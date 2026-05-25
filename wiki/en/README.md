# Documentation — `oihana/php-magento`

Composable PHP client for the [Magento 2](https://business.adobe.com/products/magento/magento-commerce.html) REST API. This documentation covers installation, OAuth1 signing, building `searchCriteria` queries, and the catalogue of typed entities (`Product`, `MediaGalleryEntry`, etc.).

## Contents

| Page | Content |
|---|---|
| [Getting started](getting-started.md) | Installation, OAuth1 configuration, first `getProduct()` call. |
| [OAuth1 signing](oauth-signing.md) | Details of `OAuthSigner`, RFC 5849, building the `Authorization` header. |
| [SearchCriteria](search-criteria.md) | Building the `searchCriteria[...]` parameters Magento expects (filters, AND/OR groups, sorting, pagination). |
| [Typed schemas](schemas.md) | Catalogue of entities (`Product`, `ProductImage`, `MediaGalleryEntry`, `ProductVideo`, …) and their constants. |

## Vocabulary

- **Host application** — the PHP application consuming `oihana/php-magento`. It provides the PSR-11 container (typically PHP-DI) and the OAuth1 configuration (4 secrets).
- **OAuth1** — the authentication protocol Magento 2 uses for REST integrations. Four secrets: `consumerKey` + `consumerSecret` (identify the integration) + `accessToken` + `accessTokenSecret` (identify the Magento admin user associated with the integration).
- **SearchCriteria** — query string format used by every Magento route that supports filtering / pagination. The `SearchCriteria` helper flattens a structured array into the key/value format Guzzle sends as query parameters.

## Source code

The package code lives under [`src/oihana/magento/`](../../src/oihana/magento/).

## See also

- [Magento 2 REST API reference](https://developer.adobe.com/commerce/webapi/rest/) — canonical reference on the Adobe Commerce side.
- [Magento 2 OAuth integration](https://developer.adobe.com/commerce/webapi/get-started/authentication/gs-authentication-oauth/) — creating an OAuth1 integration and the 4 secrets.
