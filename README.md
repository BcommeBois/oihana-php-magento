# Oihana PHP Magento

![Oihana PHP Magento](https://raw.githubusercontent.com/BcommeBois/oihana-php-magento/main/assets/images/oihana-php-magento-logo-inline-512x160.png)

Composable PHP client for the [Magento 2](https://business.adobe.com/products/magento/magento-commerce.html) REST API. Part of the **Oihana PHP** ecosystem, this package bundles an OAuth1-signed HTTP client (Guzzle), typed Magento entity schemas, composable client traits, a fluent `SearchCriteria` builder, and persistence-friendly DTOs — everything you need to integrate a Magento storefront end-to-end.

[![Latest Version](https://img.shields.io/packagist/v/oihana/php-magento.svg?style=flat-square)](https://packagist.org/packages/oihana/php-magento)
[![Total Downloads](https://img.shields.io/packagist/dt/oihana/php-magento.svg?style=flat-square)](https://packagist.org/packages/oihana/php-magento)
[![License](https://img.shields.io/packagist/l/oihana/php-magento.svg?style=flat-square)](LICENSE)

## 📚 Documentation

Full API reference (generated with phpDocumentor): `https://bcommebois.github.io/oihana-php-magento`

User guides (FR + EN) live under [`wiki/`](wiki/).

## 📦 Installation

Requires [PHP 8.4+](https://php.net/releases/) and a Magento 2 instance with REST API access. Install via [Composer](https://getcomposer.org/):

```bash
composer require oihana/php-magento
```

## ✨ What you can do

- **Talk to Magento 2 over REST** through a ready-to-use HTTP client built on Guzzle — OAuth1 signed requests (consumer key + secret + access token + secret) with automatic nonce + timestamp generation, HMAC-SHA1 / HMAC-SHA256 signatures, and proper RFC 5849 query/body parameter handling.
- **Build search criteria fluently** — the `SearchCriteria` helper turns `(field, value, condition, group, sortOrder, pageSize, currentPage)` tuples into the verbose `searchCriteria[filter_groups][...]` query parameters Magento expects.
- **Hydrate typed entities** — `Product`, `ProductImage`, `MediaGalleryEntry`, `ProductVideo`, `ProductMediaGalleryEntriesContent` and friends, with field-aware `HydrateWith` attributes for nested objects.
- **Harvest product media** — composable `MagentoProductsTrait` exposes products and media-gallery operations on top of the client; the `MediaType` and `ProductImageThumbnail` enums make image-role logic explicit.
- **Plug it anywhere** — the client is a thin wrapper around Guzzle, no framework lock-in. Pair it with `oihana/php-arango` to persist harvested products into ArangoDB, or with any other storage backend.

### Under the hood

- A consistent set of value objects and enums — `Magento`, `MagentoOption`, `MagentoParam`, `SearchCriteriaParam`, `ConditionType` — no magic strings.
- Pure-PHP transport based on [GuzzleHttp](https://github.com/guzzle/guzzle) v7. OAuth1 signing is implemented from scratch in `OAuthSigner` — no third-party OAuth library required.
- Hydration delegated to [`oihana/php-reflect`](https://github.com/BcommeBois/oihana-php-reflect) — Magento response payloads map directly to typed objects via the `HydrateWith` attribute.
- Schema constants split into trait-composed `ProductTrait`, `ThingTrait`, `MediaGalleryInterfaceTrait`, `MediaGalleryEntryTrait` — composable in your own DTOs.

## ✅ Running tests

Run all tests:

```bash
composer test
```

Run a specific test file:

```bash
composer test ./tests/oihana/magento/http/OAuthSignerTest.php
```

The unit tests cover the OAuth1 signer, the search criteria builder, and the field utility helpers — they run without a live Magento instance.

## 🛠️ Generate the documentation

We use [phpDocumentor](https://phpdoc.org/) to generate documentation into the `./docs` folder.

```bash
composer doc
```

## 🧾 License

Licensed under the [Mozilla Public License 2.0 (MPL‑2.0)](https://www.mozilla.org/en-US/MPL/2.0/).

## 👤 About the author

- Author: Marc ALCARAZ (aka eKameleon)
- Email: `marc@ooop.fr`
- Website: `https://www.ooop.fr`

## 🔗 Related packages

| Package | Description |
| --- | --- |
| [oihana/php-arango](https://github.com/BcommeBois/oihana-php-arango) | Composable toolkit for ArangoDB — document/edge models, AQL helpers, controllers. |
| [oihana/php-auth](https://github.com/BcommeBois/oihana-php-auth) | Casbin RBAC + JWT/OIDC authorization toolkit. |
| [oihana/php-core](https://github.com/BcommeBois/oihana-php-core) | Core helpers and utilities shared across the ecosystem. |
| [oihana/php-enums](https://github.com/BcommeBois/oihana-php-enums) | Typed constants and enums — no more magic strings. |
| [oihana/php-exceptions](https://github.com/BcommeBois/oihana-php-exceptions) | Framework exceptions with consistent semantics. |
| [oihana/php-files](https://github.com/BcommeBois/oihana-php-files) | File system helpers (paths, readers, writers). |
| [oihana/php-http](https://github.com/BcommeBois/oihana-php-http) | HTTP helpers — client IP, cookies, route patterns. |
| [oihana/php-openedge](https://github.com/BcommeBois/oihana-php-openedge) | Progress OpenEdge SQL toolkit (ODBC, query builder, models). |
| [oihana/php-reflect](https://github.com/BcommeBois/oihana-php-reflect) | Reflection and object hydration utilities. |
| [oihana/php-system](https://github.com/BcommeBois/oihana-php-system) | Framework helpers — controllers, models, request handling. |
