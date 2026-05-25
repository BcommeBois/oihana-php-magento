# Changelog

All notable changes to **oihana/php-magento** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial scaffold: Composer manifest, PHPUnit 12 + phpDocumentor 3 configuration, MPL-2.0 license, README, CHANGELOG, sibling-aligned folder layout (`src/`, `tests/`, `wiki/`, `assets/`).
- Source code under `src/oihana/magento/` (29 PHP files):
  - `MagentoClient.php` — entry-point Guzzle client with OAuth1 signing.
  - `http/OAuthSigner.php` — RFC 5849 OAuth1 signer (HMAC-SHA1 / HMAC-SHA256, nonce + timestamp, query + body parameter normalisation).
  - `enums/` (5 files): `Magento`, `MagentoOption`, `MagentoParam`, `SearchCriteriaParam`, `ConditionType`.
  - `commands/enums/MagentoCommandParam.php` — typed CLI parameter constants.
  - `schema/` (16 files): typed entities (`Product`, `ProductImage`, `ProductVideo`, `MediaGalleryEntry`, `MediaGalleryInterface`, `ProductMediaGalleryEntriesContent`, `ProductMediaGalleryEntriesVideoContent`, `Thing`) + their schema constants (`MagentoProp`, `MagentoImageRole`) + composable constant traits (`ProductTrait`, `ThingTrait`, `MediaGalleryEntryTrait`, `MediaGalleryInterfaceTrait`) + enums (`MediaType`, `ProductImageThumbnail`).
  - `traits/` (3 files): `MagentoClientTrait`, `HasMagentoClientTrait`, `MagentoProductsTrait`.
  - `utils/` (2 files): `Fields`, `SearchCriteria`.
- Test suite under `tests/oihana/magento/` (3 PHP files): `OAuthSignerTest`, `SearchCriteriaTest`, `FieldsTest`. Unit-only — no live Magento instance required.
- Bilingual user guides under `wiki/{fr,en}/`: README index, getting-started, OAuth1 signing, search criteria, schemas.
