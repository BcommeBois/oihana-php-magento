# OAuth1 signing

Magento 2 requires an OAuth1 signature compliant with [RFC 5849](https://datatracker.ietf.org/doc/html/rfc5849) on every REST call. The [`OAuthSigner`](../../src/oihana/magento/http/OAuthSigner.php) class builds the `Authorization: OAuth …` header automatically.

In practice you rarely instantiate `OAuthSigner` by hand: `MagentoClient` uses one internally via `MagentoClientTrait::initializeOauth()`. But understanding what it does helps you debug `401` errors.

## The 4 secrets

OAuth1 requires **two pairs** of key/secret:

| Pair | Identifies | Stored where |
|---|---|---|
| `consumerKey` + `consumerSecret` | The integration (application) | Magento Admin → System → Extensions → Integrations |
| `accessToken` + `accessTokenSecret` | The admin user associated with the integration | Same, activated after the integration is created |

All 4 values are passed to the constructor:

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

## Generating an `Authorization` header

```php
$header = $signer->createAuthHeader
(
    method     : 'GET' ,
    url        : 'https://shop.example.com/rest/V1/products/SKU-12345' ,
    parameters : [] // query + body parameters (empty for a simple GET)
) ;

// → OAuth oauth_consumer_key="...", oauth_token="...",
//          oauth_signature_method="HMAC-SHA256", oauth_nonce="...",
//          oauth_timestamp="...", oauth_version="1.0",
//          oauth_signature="..."
```

For requests with parameters (filters, pagination) or a JSON body, **all parameters must be passed in the `$parameters` argument** — the OAuth1 spec requires them to participate in the signature. `MagentoClient::execute()` orchestrates this internally.

## Signature components

`OAuthSigner::generateSignature()` returns an array containing:

| Key | Source |
|---|---|
| `oauth_consumer_key` | passed to the constructor |
| `oauth_token` | passed to the constructor |
| `oauth_signature_method` | `HMAC-SHA256` by default (configurable through `OAuthSignatureMethod`) |
| `oauth_nonce` | randomly generated on each call (`random_bytes(16)` → hex) |
| `oauth_timestamp` | `time()` at call time |
| `oauth_version` | `1.0` (constant `OAuthSigner::VERSION`) |
| `oauth_signature` | computed — HMAC of the *base string* with the key `consumerSecret&accessTokenSecret` |

The *base string* is the RFC 5849 normalised concatenation:

```
HTTP_METHOD & RAWURLENCODE(url) & RAWURLENCODE(normalized_params)
```

## Signature algorithms

Two algorithms supported by Magento 2:

| Algorithm | When to use |
|---|---|
| `HMAC-SHA1` | Magento instances earlier than 2.4 and servers that haven't enabled SHA-256. |
| `HMAC-SHA256` | **Recommended** on Magento 2.4+. This is the default. |

To force an algorithm:

```php
use oihana\enums\http\OAuthSignatureMethod ;

$signer = new OAuthSigner( /* ... */ ) ;
$signer->signatureMethod = OAuthSignatureMethod::HMAC_SHA1 ;
```

## Signature errors on the Magento side

Magento systematically returns `401 Unauthorized` when the signature is invalid. Most common causes:

| Cause | How to check |
|---|---|
| Server clock drift | Magento rejects `oauth_timestamp` more than 5 min off its clock. Synchronise via NTP. |
| `accessTokenSecret` mis-copied | Regenerate the integration in the admin and copy the 4 secrets again. |
| `baseUri` missing `https://` | OAuth1 normalises the scheme; `http://` doesn't sign the same way as `https://`. |
| Query parameters omitted from the signature | All query *and* body parameters (except raw JSON) must be in `$parameters`. |

## Testing the signature in isolation

The [`OAuthSignerTest`](../../tests/oihana/magento/http/OAuthSignerTest.php) test shows how to build a signer and verify the signature is deterministic for fixed inputs. Handy to replay a failed request outside the client.

## See also

- [Getting started](getting-started.md) — using through `MagentoClient`.
- [SearchCriteria](search-criteria.md) — query parameters that get signed.
- [RFC 5849 — OAuth 1.0](https://datatracker.ietf.org/doc/html/rfc5849) — canonical reference.
