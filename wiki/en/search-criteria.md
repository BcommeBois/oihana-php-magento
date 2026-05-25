# Building queries with `SearchCriteria`

Every Magento 2 route that supports pagination and filtering expects a specific query string format: `searchCriteria[filter_groups][0][filters][0][field]=…`. Writing these keys by hand is verbose and error-prone. The [`SearchCriteria`](../../src/oihana/magento/utils/SearchCriteria.php) class builds that format from a structured array.

## Overview

Three families of parameters:

| Family | Method | Effect on Magento side |
|---|---|---|
| **Pagination** | `setCurrentPage()`, `setPageSize()` | Which page, how many per page. |
| **Filters** | `addFilterGroup()` | Filtering conditions. Groups are combined with `AND`; filters **within the same group** are combined with `OR`. |
| **Sorting** | `addSortOrder()` | Multiple sorts chain in order. |

`SearchCriteria::get()` returns the flattened array ready to pass as `$queryParams` to Guzzle.

## Simple case — default pagination

```php
use oihana\magento\utils\SearchCriteria ;

$criteria = new SearchCriteria() ;
print_r( $criteria->get() ) ;
// [
//     'searchCriteria[currentPage]' => 1,
//     'searchCriteria[pageSize]'    => 20
// ]
```

Default values: page 1, 20 items per page. These are the `SearchCriteria::DEFAULT_CURRENT_PAGE` and `SearchCriteria::DEFAULT_PAGE_SIZE` constants.

## Custom pagination

```php
use oihana\magento\enums\SearchCriteriaParam ;

$criteria = new SearchCriteria
([
    SearchCriteriaParam::CURRENT_PAGE => 3   ,
    SearchCriteriaParam::PAGE_SIZE    => 100 ,
]) ;
```

Or fluently:

```php
$criteria
    ->setCurrentPage( 3   )
    ->setPageSize   ( 100 ) ;
```

## Simple filters — `AND` between groups

To filter products where `status = 1` **AND** `type = simple`, put each filter in its own group.

```php
use oihana\magento\enums\ConditionType ;
use oihana\magento\enums\SearchCriteriaParam ;

$criteria
    ->addFilterGroup
    ([
        [
            SearchCriteriaParam::FIELD          => 'status'         ,
            SearchCriteriaParam::VALUE          => '1'              ,
            SearchCriteriaParam::CONDITION_TYPE => ConditionType::EQ ,
        ]
    ])
    ->addFilterGroup
    ([
        [
            SearchCriteriaParam::FIELD          => 'type_id'         ,
            SearchCriteriaParam::VALUE          => 'simple'          ,
            SearchCriteriaParam::CONDITION_TYPE => ConditionType::EQ ,
        ]
    ]) ;
```

## Compound filters — `OR` within a group

To filter products where `status = 1` **OR** `status = 2`, put both filters in **the same group**.

```php
$criteria->addFilterGroup
([
    [
        SearchCriteriaParam::FIELD          => 'status'          ,
        SearchCriteriaParam::VALUE          => '1'               ,
        SearchCriteriaParam::CONDITION_TYPE => ConditionType::EQ ,
    ],
    [
        SearchCriteriaParam::FIELD          => 'status'          ,
        SearchCriteriaParam::VALUE          => '2'               ,
        SearchCriteriaParam::CONDITION_TYPE => ConditionType::EQ ,
    ],
]) ;
```

## Operator catalogue

The [`ConditionType`](../../src/oihana/magento/enums/ConditionType.php) enum groups every operator Magento supports:

| Constant | SQL value | Description |
|---|---|---|
| `EQ` | `=` | Equal |
| `NEQ` | `!=` | Not equal |
| `GT` | `>` | Greater than |
| `GTEQ` | `>=` | Greater than or equal |
| `LT` | `<` | Less than |
| `LTEQ` | `<=` | Less than or equal |
| `LIKE` | `LIKE` | Partial match (with `%`) |
| `NLIKE` | `NOT LIKE` | Inverse |
| `IN` | `IN (…)` | Value in a list (separator `,`) |
| `NIN` | `NOT IN (…)` | Inverse |
| `NULL` | `IS NULL` | No value (the filter value is ignored) |
| `NOT_NULL` | `IS NOT NULL` | Inverse |

## Sorting

```php
use oihana\enums\Order ;

$criteria
    ->addSortOrder( 'created_at' , Order::DESC )
    ->addSortOrder( 'sku'        , Order::ASC  ) ;
```

Magento applies sorts in the order they are added (`created_at DESC, sku ASC`).

## Reset

Four reset methods, from most targeted to most global:

| Method | Resets |
|---|---|
| `resetFilterGroups()` | All filters |
| `resetSortOrders()` | All sorts |
| `resetPaging()` | `currentPage` and `pageSize` to defaults |
| `reset()` | The three above in one call |

All return `$this` to stay chainable.

## Full example

```php
$criteria = ( new SearchCriteria() )
    ->addFilterGroup
    ([
        [ SearchCriteriaParam::FIELD => 'status'  , SearchCriteriaParam::VALUE => '1'      , SearchCriteriaParam::CONDITION_TYPE => ConditionType::EQ   ] ,
        [ SearchCriteriaParam::FIELD => 'type_id' , SearchCriteriaParam::VALUE => 'simple' , SearchCriteriaParam::CONDITION_TYPE => ConditionType::EQ   ] ,
    ])
    ->addFilterGroup
    ([
        [ SearchCriteriaParam::FIELD => 'updated_at' , SearchCriteriaParam::VALUE => '2026-01-01 00:00:00' , SearchCriteriaParam::CONDITION_TYPE => ConditionType::GTEQ ] ,
    ])
    ->addSortOrder( 'updated_at' , Order::DESC )
    ->setCurrentPage( 1   )
    ->setPageSize   ( 100 ) ;

// First group : status = 1 OR type_id = simple
// AND
// Second group : updated_at >= '2026-01-01 00:00:00'
// SORT BY updated_at DESC
// page 1, 100 items
```

## Using with `MagentoClient`

```php
$response = $client->call
(
    endpoint     : 'products' ,
    method       : 'GET' ,
    data         : null ,
    queryParams  : $criteria->get() ,
) ;
```

## See also

- [Getting started](getting-started.md) — first calls.
- [Typed schemas](schemas.md) — hydrating the response into `Product` objects.
- [Magento REST API — Search criteria](https://developer.adobe.com/commerce/webapi/rest/use-rest/performing-searches/) — canonical reference.
