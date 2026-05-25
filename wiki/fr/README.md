# Documentation — `oihana/php-magento`

Client PHP composable pour l'API REST [Magento 2](https://business.adobe.com/products/magento/magento-commerce.html). Cette documentation couvre l'installation, la signature OAuth1, la construction de requêtes `searchCriteria`, et le catalogue des entités typées (`Product`, `MediaGalleryEntry`, etc.).

## Sommaire

| Page | Contenu |
|---|---|
| [Démarrage rapide](getting-started.md) | Installation, configuration OAuth1, premier appel `getProduct()`. |
| [Signature OAuth1](oauth-signing.md) | Détail de `OAuthSigner`, RFC 5849, génération du header `Authorization`. |
| [SearchCriteria](search-criteria.md) | Construction des paramètres `searchCriteria[...]` attendus par Magento (filtres, groupes AND/OR, tris, pagination). |
| [Schémas typés](schemas.md) | Catalogue des entités (`Product`, `ProductImage`, `MediaGalleryEntry`, `ProductVideo`, …) et de leurs constantes. |

## Vocabulaire

- **Application hôte** — l'application PHP qui consomme `oihana/php-magento`. Elle fournit le conteneur PSR-11 (typiquement PHP-DI) et la configuration OAuth1 (4 secrets).
- **OAuth1** — protocole d'authentification utilisé par Magento 2 pour les intégrations REST. Quatre secrets : `consumerKey` + `consumerSecret` (identifient l'intégration) + `accessToken` + `accessTokenSecret` (identifient l'utilisateur admin Magento associé).
- **SearchCriteria** — format de query string utilisé par toutes les routes Magento qui supportent le filtrage / la pagination. Le helper `SearchCriteria` aplatit un tableau structuré dans le format clé/valeur que Guzzle envoie en query.

## Code source

Le code du package vit sous [`src/oihana/magento/`](../../src/oihana/magento/).

## Voir aussi

- [Magento 2 REST API reference](https://developer.adobe.com/commerce/webapi/rest/) — référence canonique côté Adobe Commerce.
- [Magento 2 OAuth integration](https://developer.adobe.com/commerce/webapi/get-started/authentication/gs-authentication-oauth/) — création d'une intégration OAuth1 et des 4 secrets.
