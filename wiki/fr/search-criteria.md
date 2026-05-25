# Construire des requêtes avec `SearchCriteria`

Toutes les routes Magento 2 qui supportent la pagination et le filtrage attendent un format de query string spécifique : `searchCriteria[filter_groups][0][filters][0][field]=…`. Écrire ces clés à la main est verbeux et source d'erreurs. La classe [`SearchCriteria`](../../src/oihana/magento/utils/SearchCriteria.php) construit ce format à partir d'un tableau structuré.

## Vue d'ensemble

Trois familles de paramètres :

| Famille | Méthode | Effet côté Magento |
|---|---|---|
| **Pagination** | `setCurrentPage()`, `setPageSize()` | Quelle page, combien par page. |
| **Filtres** | `addFilterGroup()` | Conditions de filtrage. Les groupes sont en `AND` entre eux, les filtres **dans un même groupe** sont en `OR`. |
| **Tris** | `addSortOrder()` | Plusieurs tris ajoutés s'enchaînent. |

`SearchCriteria::get()` retourne le tableau aplati prêt à passer dans `$queryParams` côté Guzzle.

## Cas simple — pagination par défaut

```php
use oihana\magento\utils\SearchCriteria ;

$criteria = new SearchCriteria() ;
print_r( $criteria->get() ) ;
// [
//     'searchCriteria[currentPage]' => 1,
//     'searchCriteria[pageSize]'    => 20
// ]
```

Valeurs par défaut : page 1, 20 éléments par page. Ce sont les constantes `SearchCriteria::DEFAULT_CURRENT_PAGE` et `SearchCriteria::DEFAULT_PAGE_SIZE`.

## Pagination personnalisée

```php
use oihana\magento\enums\SearchCriteriaParam ;

$criteria = new SearchCriteria
([
    SearchCriteriaParam::CURRENT_PAGE => 3   ,
    SearchCriteriaParam::PAGE_SIZE    => 100 ,
]) ;
```

Ou par méthode fluide :

```php
$criteria
    ->setCurrentPage( 3   )
    ->setPageSize   ( 100 ) ;
```

## Filtres simples — `AND` entre groupes

Pour filtrer les produits dont `status = 1` **ET** `type = simple`, on met chaque filtre dans son propre groupe.

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

## Filtres composés — `OR` dans un groupe

Pour filtrer les produits dont `status = 1` **OU** `status = 2`, on met les deux filtres dans **le même groupe**.

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

## Catalogue des opérateurs

L'enum [`ConditionType`](../../src/oihana/magento/enums/ConditionType.php) regroupe tous les opérateurs supportés par Magento :

| Constante | Valeur SQL | Description |
|---|---|---|
| `EQ` | `=` | Égal |
| `NEQ` | `!=` | Différent |
| `GT` | `>` | Supérieur à |
| `GTEQ` | `>=` | Supérieur ou égal |
| `LT` | `<` | Inférieur à |
| `LTEQ` | `<=` | Inférieur ou égal |
| `LIKE` | `LIKE` | Recherche partielle (avec `%`) |
| `NLIKE` | `NOT LIKE` | Inverse du précédent |
| `IN` | `IN (…)` | Valeur dans une liste (séparateur `,`) |
| `NIN` | `NOT IN (…)` | Inverse |
| `NULL` | `IS NULL` | Pas de valeur (la valeur du filtre est ignorée) |
| `NOT_NULL` | `IS NOT NULL` | Inverse |

## Tris

```php
use oihana\enums\Order ;

$criteria
    ->addSortOrder( 'created_at' , Order::DESC )
    ->addSortOrder( 'sku'        , Order::ASC  ) ;
```

Magento applique les tris dans l'ordre d'ajout (`created_at DESC, sku ASC`).

## Réinitialiser

Quatre méthodes de reset, du plus ciblé au plus global :

| Méthode | Reset |
|---|---|
| `resetFilterGroups()` | Tous les filtres |
| `resetSortOrders()` | Tous les tris |
| `resetPaging()` | `currentPage` et `pageSize` à leurs valeurs par défaut |
| `reset()` | Les trois ci-dessus en une fois |

Toutes retournent `$this` pour rester *chainables*.

## Exemple complet

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

// Premier groupe : status = 1 OR type_id = simple
// AND
// Second groupe : updated_at >= '2026-01-01 00:00:00'
// SORT BY updated_at DESC
// page 1, 100 items
```

## Utilisation avec `MagentoClient`

```php
$response = $client->call
(
    endpoint     : 'products' ,
    method       : 'GET' ,
    data         : null ,
    queryParams  : $criteria->get() ,
) ;
```

## Voir aussi

- [Démarrage rapide](getting-started.md) — premiers appels.
- [Schémas typés](schemas.md) — hydrater la réponse en objets `Product`.
- [Magento REST API — Search criteria](https://developer.adobe.com/commerce/webapi/rest/use-rest/performing-searches/) — référence canonique.
