# Signature OAuth1

Magento 2 exige une signature OAuth1 conforme à la [RFC 5849](https://datatracker.ietf.org/doc/html/rfc5849) sur chaque appel REST. La classe [`OAuthSigner`](../../src/oihana/magento/http/OAuthSigner.php) construit l'en-tête `Authorization: OAuth …` automatiquement.

En pratique, on n'instancie presque jamais `OAuthSigner` à la main : `MagentoClient` en utilise un en interne via `MagentoClientTrait::initializeOauth()`. Mais comprendre ce qu'il fait permet de débugger les erreurs `401`.

## Les 4 secrets

OAuth1 exige **deux paires** de clé/secret :

| Paire | Identifie | Stocké où |
|---|---|---|
| `consumerKey` + `consumerSecret` | L'intégration (application) | Magento Admin → System → Extensions → Integrations |
| `accessToken` + `accessTokenSecret` | L'utilisateur admin associé à l'intégration | Idem, activés après la création de l'intégration |

Les 4 valeurs sont passées au constructeur :

```php
use oihana\magento\http\OAuthSigner ;

$signer = new OAuthSigner
(
    consumerKey       : $_ENV[ 'MAGENTO_CONSUMER_KEY'         ] ,
    consumerSecret    : $_ENV[ 'MAGENTO_CONSUMER_SECRET'      ] ,
    accessToken       : $_ENV[ 'MAGENTO_ACCESS_TOKEN'         ] ,
    accessTokenSecret : $_ENV[ 'MAGENTO_ACCESS_TOKEN_SECRET'  ]
) ;
```

## Générer un header `Authorization`

```php
$header = $signer->createAuthHeader
(
    method     : 'GET' ,
    url        : 'https://shop.example.com/rest/V1/products/SKU-12345' ,
    parameters : [] // paramètres query + body (vide pour un GET simple)
) ;

// → OAuth oauth_consumer_key="...", oauth_token="...",
//          oauth_signature_method="HMAC-SHA256", oauth_nonce="...",
//          oauth_timestamp="...", oauth_version="1.0",
//          oauth_signature="..."
```

Pour les requêtes avec paramètres (filtres, pagination) ou avec un corps JSON, **tous les paramètres doivent être passés en argument `$parameters`** — la spec OAuth1 exige qu'ils participent à la signature. C'est `MagentoClient::execute()` qui orchestre ça en interne.

## Composants de la signature

`OAuthSigner::generateSignature()` retourne un tableau qui contient :

| Clé | Source |
|---|---|
| `oauth_consumer_key` | passé au constructeur |
| `oauth_token` | passé au constructeur |
| `oauth_signature_method` | `HMAC-SHA256` par défaut (configurable via `OAuthSignatureMethod`) |
| `oauth_nonce` | généré aléatoirement à chaque appel (`random_bytes(16)` → hex) |
| `oauth_timestamp` | `time()` au moment de l'appel |
| `oauth_version` | `1.0` (constante `OAuthSigner::VERSION`) |
| `oauth_signature` | calculée — HMAC du *base string* avec la clé `consumerSecret&accessTokenSecret` |

Le *base string* est la concaténation normalisée RFC 5849 :

```
HTTP_METHOD & RAWURLENCODE(url) & RAWURLENCODE(normalized_params)
```

## Algorithmes de signature

Deux algorithmes supportés par Magento 2 :

| Algorithme | Quand l'utiliser |
|---|---|
| `HMAC-SHA1` | Instances Magento antérieures à 2.4 et serveurs qui n'ont pas activé SHA-256. |
| `HMAC-SHA256` | **Recommandé** sur Magento 2.4+. C'est la valeur par défaut. |

Pour forcer un algorithme :

```php
use oihana\enums\http\OAuthSignatureMethod ;

$signer = new OAuthSigner( /* ... */ ) ;
$signer->signatureMethod = OAuthSignatureMethod::HMAC_SHA1 ;
```

## Erreurs de signature côté Magento

Magento renvoie systématiquement `401 Unauthorized` quand la signature est invalide. Causes les plus fréquentes :

| Cause | Comment vérifier |
|---|---|
| Horloge serveur dérive | Magento rejette les `oauth_timestamp` à plus de 5 min de son horloge. Synchroniser via NTP. |
| `accessTokenSecret` mal copié | Régénérer l'intégration côté admin et copier les 4 secrets à nouveau. |
| `baseUri` ne contient pas `https://` | OAuth1 normalise le scheme ; un `http://` ne signe pas la même chose qu'un `https://`. |
| Paramètres query oubliés dans la signature | Tous les paramètres de la query *et* du body (sauf JSON) doivent être inclus dans `$parameters`. |

## Tester la signature isolément

Le test [`OAuthSignerTest`](../../tests/oihana/magento/http/OAuthSignerTest.php) montre comment construire un signer et vérifier que la signature est déterministe pour des entrées fixes. Pratique pour rejouer une requête échouée en dehors du client.

## Voir aussi

- [Démarrage rapide](getting-started.md) — utilisation via `MagentoClient`.
- [SearchCriteria](search-criteria.md) — paramètres query envoyés en signature.
- [RFC 5849 — OAuth 1.0](https://datatracker.ietf.org/doc/html/rfc5849) — référence canonique.
