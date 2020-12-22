Jisly
========

[![Build Status](https://travis-ci.org/r0mdau/jisly.svg?branch=master)](https://travis-ci.org/r0mdau/jisly)
[![Coverage Status](https://coveralls.io/repos/github/r0mdau/jisly/badge.svg?branch=master)](https://coveralls.io/github/r0mdau/jisly?branch=master)
[![Known Vulnerabilities](https://snyk.io/test/github/r0mdau/jisly/badge.svg?targetFile=composer.lock)](https://snyk.io/test/github/r0mdau/jisly?targetFile=composer.lock)

Simple NoSQL database written in PHP, data stored in JSON on filesystem.

The main goal of Jisly is to allow you to quickly start your project with the possibility of file system storage and 
NoSQL query syntax.

**Concurrent access is managed !**

# Definitions

1. Each document has a unique identifier called `_rid`.
2. Each collection is physically represented by a file.
3. The files are stored in a single working directory. The Jisly class is instantiated with the path to this directory 
as a parameter.

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

Return the first document that as a `name` attribute with `19` as an object value :
```php
$result = $database->collection($file)->findOne(
  [
    "name" => 19
  ]
);
```

### Update method

For the modification, the documents concerned are entirely replaced by the second **array()** given in parameter.

*You must first find all the documents to replace to provide the `_rid` attribute to the update method.*

Modify the only document in the collection whose value $rid to the `_rid` attribute :
```php
$successBool = $database->collection($file)->update(
  $rid,
  [
    "firstname" => "Georges", 
    "name" => "lucas"
  ]
);
```