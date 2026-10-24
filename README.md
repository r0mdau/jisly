Jisly
========

![PHPUnit](https://github.com/r0mdau/jisly/actions/workflows/unit-tests.yml/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/r0mdau/jisly/badge.svg?branch=main)](https://coveralls.io/github/r0mdau/jisly?branch=main)
[![Known Vulnerabilities](https://snyk.io/test/github/r0mdau/jisly/badge.svg)](https://snyk.io/test/github/r0mdau/jisly)

The smallest PHP lightweight NoSQL database library, flat file JSON.

The main goal of Jisly is to allow you to quickly start your project with the possibility of memory and flat-file
storage using a NoSQL (document oriented) query syntax.

[Version française ici](https://github.com/r0mdau/jisly/blob/main/README.fr.md)

**Concurrent access is managed !**

# How to install and load

This lib can be found on [Packagist](https://packagist.org/packages/r0mdau/jisly)

```
composer require r0mdau/jisly:^2.0
```

## Loading the library

Jisly relies on the [PSR-4 specification](https://www.php-fig.org/psr/psr-4/) for autoloading classes from file paths.

```
use Jisly\JislyCollection;

public function saveCart(): void {
    $jisly = new JislyCollection("/tmp/data", "cart");
    ...
}
```

# Definitions

1. Each document has a unique identifier called `_rid`. Searches match `_rid` exactly (`"0123"` does not find `"123"`),
while other attributes use loose comparison (`"123"` finds `123`).
2. Each collection is physically represented by a file.
3. The files are stored in a single working directory. The Jisly class is instantiated with the path to this directory
as a parameter.
4. Each time you CRUD something, all datas are stored in memory.
5. And datas are saved on filesystem.

# Examples of use

## Initialization of the class

`$directory` contains the path to the directory where the files (=collections) of the data model will be stored.

```php
$database = new Jisly($directory);
```

## To access a collection

`$name` contains the name of the collection we want to request. Example : `user`.

Returns an object **JislyCollection** :
```php
$database->collection($name);
```

Warning : each first time you access a collection, all datas are stored in memory.

## To call a collection

**PREAMBLE :**
The Insert, Update, Delete methods return a boolean, `true` if the action went well, `false` otherwise.

### Insert method

Insert the array into the specified collection in JSON format and assigns a unique `_rid` identifier to the document if
it has not been specified :
```php
$successBool = $database->collection($file)->insert(
  [
    "name" => "Lucas",
    "firstname" => "Georges"
  ]
);
```

### Delete method

*You must first find all documents to delete to provide the `_rid` attribute to the delete method.*

Remove the only document in the collection which has the value `$rid` to the attribute `_rid` :
```php
$successBool = $database->collection($file)->delete($rid);
```

### Select method

Returns all documents in the collection in an **array()** of objects :
```php
$results = $database->collection($file)->find();
```

Return all documents in the collection that have a `name` attribute with `Lucas` as value in an **array()** of objects :
```php
$results = $database->collection($file)->find(
  [
    "name" => "Lucas"
  ]
);
```

Return the first document that has a `name` attribute with `19` as an object value, or `null` if none matches :
```php
$result = $database->collection($file)->findOne(
  [
    "name" => 19
  ]
);
```

#### Logical operators OR and AND

These two logical operators can be used on `find` and `findOne` methods.

If no logical operator is provided, **OR** is used.

Return all documents in the collection that have a `name` attribute with `Lucas` **OR** a `firstname` attribute with
`Georges` as values in an **array()** of objects :
```php
$result = $database->collection($file)->find(
  [
    "firstname" => "Georges",
    "name" => "Lucas"
  ], JislyCollection::LOGICAL_OR
);
```

Return the first document in the collection that have a `name` attribute with `Lucas` **AND** a `firstname` attribute with
`Georges` as an object value :
```php
$result = $database->collection($file)->findOne(
  [
    "firstname" => "Georges",
    "name" => "Lucas"
  ], JislyCollection::LOGICAL_AND
);
```

### Update method

For the modification, the documents concerned are entirely replaced by the second **array()** given in parameter. The
document keeps its `_rid`, even if the array omits it or gives another one.

*You must first find all the documents to replace to provide the `_rid` attribute to the update method.*

Modify the only document in the collection whose value $rid to the `_rid` attribute :
```php
$successBool = $database->collection($file)->update(
  $rid,
  [
    "firstname" => "Georges",
    "name" => "Lucas"
  ]
);
```

# Benchmarks

The benchmark suite lives in `benchmark/` and runs with [PHPBench](https://github.com/phpbench/phpbench):

```
composer bench
```

Each operation is timed against collections of 100, 1,000 and 10,000 documents. Indicative results (mode per call,
PHP 8.3, x86_64 cloud VM, local disk):

| Operation                  | 100 docs | 1,000 docs | 10,000 docs |
|----------------------------|---------:|-----------:|------------:|
| Open a collection          |  0.13 ms |    1.2 ms  |      15 ms  |
| `insert()`                 |  0.01 ms |   0.01 ms  |    0.01 ms  |
| `find()` all               |   < 1 µs |     < 1 µs |      < 1 µs |
| `find()` with `OR`         |  0.06 ms |    0.6 ms  |     7.7 ms  |
| `find()` with `AND`        |  0.07 ms |    0.6 ms  |     8.0 ms  |
| `findOne()` by `_rid`      |   < 2 µs |     < 2 µs |      < 2 µs |
| `update()`                 |  0.63 ms |    3.0 ms  |      36 ms  |
| `delete()`                 |  0.63 ms |    3.2 ms  |      32 ms  |

Opening a collection reads the whole file, searches scan every document, and `update()` / `delete()` rewrite the
whole file, so these grow linearly with the collection size. `insert()` only appends a line and a search on `_rid`
only looks up that document, so their cost stays flat.
