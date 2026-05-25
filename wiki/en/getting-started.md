# Getting started

This page walks through the three minimal steps to query a Magento 2 instance from PHP with `oihana/php-magento`:

1. Install the package.
2. Create an OAuth1 integration on the Magento side and retrieve the 4 secrets.
3. Instantiate `MagentoClient` and make a first call.

## Step 1 — Installation

Prerequisites:

- PHP 8.4 or higher.
- A Magento 2 instance reachable over HTTPS with the REST API enabled.
- A PSR-11 container in the host application (the examples below use [PHP-DI](https://php-di.org/)).

Install via Composer:

```bash
composer require oihana/php-magento
```

## Step 2 — Create the OAuth1 integration in Magento

Magento 2 REST authentication uses **OAuth1 with 4 secrets**:

| Secret | Where it comes from |
|---|---|
| `consumerKey` | Magento Admin → System → Extensions → Integrations → New Integration |
| `consumerSecret` | Same, displayed after activation |
| `accessToken` | Same, tied to the Magento admin user associated with the integration |
| `accessTokenSecret` | Same |

See the [official Magento documentation](https://developer.adobe.com/commerce/webapi/get-started/authentication/gs-authentication-oauth/) for the details. The 4 secrets must be stored in a vault (env vars, Vault, secrets file) and **never committed** in plaintext.

## Step 3 — First call

`MagentoClient` is a pre-configured Guzzle client that signs every request automatically. You pass it a PHP-DI container and an init array.

```php
use DI\Container ;
use oihana\magento\MagentoClient ;
use oihana\magento\enums\Magento ;

$container = /* PHP-DI or any other PSR-11 container */ ;

$client = new MagentoClient( $container ,
[
    Magento::BASE_URI        => 'https://shop.example.com/rest/V1/' ,
    Magento::CONSUMER_KEY    => $_ENV[ 'MAGENTO_CONSUMER_KEY'     ] ,
    Magento::CONSUMER_SECRET => $_ENV[ 'MAGENTO_CONSUMER_SECRET'  ] ,
    Magento::TOKEN           => $_ENV[ 'MAGENTO_ACCESS_TOKEN'     ] ,
    Magento::TOKEN_SECRET    => $_ENV[ 'MAGENTO_ACCESS_TOKEN_SECRET' ] ,
    Magento::MAX_RETRIES     => 3 ,
]) ;

// Test the connection (calls GET /modules internally)
if ( $client->isConnected() )
{
    echo "Magento reachable" . PHP_EOL ;
}

// Load a product by SKU
$product = $client->getProduct( 'SKU-12345' ) ;
print_r( $product ) ;
```

## What's next?

- To **list products with filters + pagination**, read the [SearchCriteria](search-criteria.md) page.
- To understand **how the OAuth1 signature is computed**, read the [OAuth1 signing](oauth-signing.md) page.
- To **hydrate responses into typed objects**, read the [Typed schemas](schemas.md) page.

## Common pitfalls

| Symptom | Likely cause |
|---|---|
| `Error401` on every call | At least one of the 4 OAuth1 secrets is wrong or expired. Regenerate in the Magento back-office. |
| `Error404` on an endpoint that exists | `baseUri` misconfigured. Check the `/rest/V1/` suffix (not `/rest/V2/` nor `/api/rest/`). |
| Empty response while the product exists | The admin user associated with the integration lacks `Catalog > Products` permissions. Widen the scope in the integration. |
| Timeout on large listings | Increase `Magento::MAX_RETRIES` or paginate with smaller pages through `SearchCriteria::setPageSize()`. |

## See also

- [OAuth1 signing](oauth-signing.md)
- [SearchCriteria](search-criteria.md)
- [Typed schemas](schemas.md)
