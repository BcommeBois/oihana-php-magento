# Démarrage rapide

Cette page enchaîne les trois étapes minimales pour interroger une instance Magento 2 depuis PHP avec `oihana/php-magento` :

1. Installer le package.
2. Créer une intégration OAuth1 côté Magento et récupérer les 4 secrets.
3. Instancier `MagentoClient` et faire un premier appel.

## Étape 1 — Installation

Pré-requis :

- PHP 8.4 ou supérieur.
- Une instance Magento 2 accessible en HTTPS avec l'API REST activée.
- Un conteneur PSR-11 dans l'application hôte (les exemples ci-dessous utilisent [PHP-DI](https://php-di.org/)).

Installation via Composer :

```bash
composer require oihana/php-magento
```

## Étape 2 — Créer l'intégration OAuth1 côté Magento

L'authentification REST Magento 2 utilise **OAuth1 à 4 secrets** :

| Secret | D'où il vient |
|---|---|
| `consumerKey` | Magento Admin → System → Extensions → Integrations → New Integration |
| `consumerSecret` | Idem, affiché après activation |
| `accessToken` | Idem, lié à l'utilisateur admin Magento associé à l'intégration |
| `accessTokenSecret` | Idem |

Voir la [documentation officielle Magento](https://developer.adobe.com/commerce/webapi/get-started/authentication/gs-authentication-oauth/) pour le détail. Les 4 secrets sont à stocker dans un coffre (env vars, Vault, secrets file) et **jamais commités** en clair.

## Étape 3 — Premier appel

`MagentoClient` est un client Guzzle pré-configuré qui signe automatiquement chaque requête. On lui passe un conteneur PHP-DI et un tableau d'initialisation.

```php
use DI\Container ;
use oihana\magento\MagentoClient ;
use oihana\magento\enums\Magento ;

$container = /* PHP-DI ou autre conteneur PSR-11 */ ;

$client = new MagentoClient( $container ,
[
    Magento::BASE_URI        => 'https://shop.example.com/rest/V1/' ,
    Magento::CONSUMER_KEY    => $_ENV[ 'MAGENTO_CONSUMER_KEY'     ] ,
    Magento::CONSUMER_SECRET => $_ENV[ 'MAGENTO_CONSUMER_SECRET'  ] ,
    Magento::TOKEN           => $_ENV[ 'MAGENTO_ACCESS_TOKEN'     ] ,
    Magento::TOKEN_SECRET    => $_ENV[ 'MAGENTO_ACCESS_TOKEN_SECRET' ] ,
    Magento::MAX_RETRIES     => 3 ,
]) ;

// Tester la connexion (appelle GET /modules en interne)
if ( $client->isConnected() )
{
    echo "Magento joignable" . PHP_EOL ;
}

// Charger un produit par SKU
$product = $client->getProduct( 'SKU-12345' ) ;
print_r( $product ) ;
```

## Et après ?

- Pour **lister des produits avec filtres + pagination**, lire la page [SearchCriteria](search-criteria.md).
- Pour comprendre **comment la signature OAuth1 est calculée**, lire la page [Signature OAuth1](oauth-signing.md).
- Pour **hydrater les réponses en objets typés**, lire la page [Schémas typés](schemas.md).

## Pièges fréquents

| Symptôme | Cause probable |
|---|---|
| `Error401` à chaque appel | Au moins un des 4 secrets OAuth1 est faux ou périmé. Régénérer dans le back-office Magento. |
| `Error404` sur un endpoint qui existe | `baseUri` mal réglé. Vérifier le suffixe `/rest/V1/` (pas `/rest/V2/` ni `/api/rest/`). |
| Réponse vide alors que le produit existe | L'utilisateur admin associé à l'intégration n'a pas les permissions `Catalog > Products`. Élargir le scope dans l'intégration. |
| Timeout sur de gros listings | Augmenter `Magento::MAX_RETRIES` ou paginer plus finement via `SearchCriteria::setPageSize()`. |

## Voir aussi

- [Signature OAuth1](oauth-signing.md)
- [SearchCriteria](search-criteria.md)
- [Schémas typés](schemas.md)
