Jisly
========

[![Build Status](https://travis-ci.org/r0mdau/jisly.svg?branch=master)](https://travis-ci.org/r0mdau/jisly)
[![Coverage Status](https://coveralls.io/repos/github/r0mdau/jisly/badge.svg?branch=master)](https://coveralls.io/github/r0mdau/jisly?branch=master)
[![Known Vulnerabilities](https://snyk.io/test/github/r0mdau/jisly/badge.svg?targetFile=composer.lock)](https://snyk.io/test/github/r0mdau/jisly?targetFile=composer.lock)

Bibliothèque de base de données PHP légère NoSQL, fichier plat JSON.

L'objectif principal de Jisly est de vous permettre de démarrer rapidement votre projet avec la possibilité de stockage
 en mémoire et fichier plat à l'aide d'une syntaxe de requête NoSQL (orientée document).

**Les accès concurrents sont gérés !**

# Définitions

1. Chaque document possède un identifiant unique dénommé `_rid`.
2. Chaque collection est représentée physiquement par un fichier.
3. Les fichiers sont stockés dans un seul répertoire de travail. La classs Jisly est instanciée avec le chemin vers ce 
répertoire en paramètre.
4. Chaque fois que vous faites un CRUD, toutes les données sont stockées en mémoire.
5. Et les données sont sauvegardées sur le système de fichiers.

# Exemples d'utilisation

## Initialisation de la classe :

`$directory` contient le chemin vers le répertoire où seront stockés les fichiers (=collections) du modèle de données.

```php
$database = new Jisly($directory);
```

## Pour accéder à une collection :

`$nom` contient le nom de la collection que l'on souhaite requêter. Exemple : `user`

Retourne un objet **JislyCollection** :
```php
$database->collection($nom);
```

Attention : chaque premier accès à une collection lance le stockage des données en mémoire.

## Pour requêter une collection :

**PREAMBULE :**
Les méthodes Insert, Update, Delete retournent un booleen, `true` si l'action s'est bien passée, `false` dans le cas contraire

### Méthode d'insertion :

Insère le tableau dans la collection spécifiée au format json et attribue un identifiant `_rid` unique au document 
si celui-ci n'a pas été spécifié :
```php
$successBool = $database->collection($file)->insert(
  [
    "nom" => "dauby", 
    "prenom" => "romain"
  ]
);
```

### Méthode de suppression :

*Il faut au préalable rechercher tous les documents à effacer pour fournir l'attribut `_rid` à la méthode delete*

Supprime le seul document de la collection qui a pour valeur `$rid` à l'attribut `_rid` :
```php
$successBool = $database->collection($file)->delete($rid);
```

### Méthode de sélection :

Retourne tous les documents de la collection dans un **array()** d'objets :
```php
$results = $database->collection($file)->find();
```

Retourne tous les documents de la collection qui ont un attribut `nom` avec `dauby` comme valeur dans un **array()** 
d'objets :
```php
$results = $database->collection($file)->find(
  [
    "nom" => "dauby"
  ]
);
```

Retourne le premier document qui a un attribut `nom` avec `19` comme valeur sous forme d'objet :
```php
$result = $database->collection($file)->findOne(
  [
    "nom" => 19
  ]
);
```

#### Opérateurs logiques OR et AND

Ces deux opérateurs logiques peuvent être utilisés avec les méthodes `find` et `findOne`.

Si vous ne spécifiez pas d'opérateur logique, le **OR** sera utilisé.

Retourne tous les documents de la collection qui on un attribut `nom` avec `Lucas` **OU** un attribut `prenom` 
avec `Georges` dans un **array()** d'objets :
```php
$result = $database->collection($file)->find(
  [
    "prenom" => "Georges", 
    "nom" => "Lucas"
  ], JislyCollection::LOGICAL_OR
);
```

Retourne le premier document qui a un attribut `nom` avec `Lucas` **ET** un attribut `prenom`
avec `Georges` sous forme d'objet :
```php
$result = $database->collection($file)->findOne(
  [
    "prenom" => "Georges", 
    "nom" => "Lucas"
  ], JislyCollection::LOGICAL_AND
);
```

### Méthode de modification :

Pour la modification, les documents concernés sont entièrement remplacés par le second **array()** passé en paramètre.

*Il faut au préalable rechercher tous les documents à remplacer pour fournir l'attribut `_rid` à la méthode update*

Modifie le seul document de la collection qui a pour valeur $rid à l'attribut `_rid` :
```php
$successBool = $database->collection($file)->update(
  $rid,
  [
    "prenom" => "georges", 
    "nom" => "lucas"
  ]
);
```