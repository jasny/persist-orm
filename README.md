Jasny Entity Mapper
===

[![Build Status](https://travis-ci.org/jasny/entity-mapper.svg?branch=master)](https://travis-ci.org/jasny/entity-mapper)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jasny/entity-mapper/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jasny/entity-mapper/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jasny/entity-mapper/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jasny/entity-mapper/?branch=master)
[![Packagist Stable Version](https://img.shields.io/packagist/v/jasny/entity-mapper.svg)](https://packagist.org/packages/jasny/entity-mapper)
[![Packagist License](https://img.shields.io/packagist/l/jasny/entity-mapper.svg)](https://packagist.org/packages/jasny/entity-mapper)

Data mapping for [Jasny Entity](https://github.com/jasny/entity).

* [Create new entity](#create-new-entity)
* [Convert data into entities](#convert-data-into-entities)
* [Save entity to persistent storage](#save-entities-to-persistent-storage)
* [Delete entity from persistent storage](#delete-entities-from-persistent-storage) 

_All objects are immutable._

Installation
---

    composer require jasny/entity-mapper

Usage
---

### Create new entity

```php
use Jasny\EntityMapper\EntityMapper;

$mapper = new EntityMapper();

$entity = $entityMapper->create(User::class);
```

Arguments will be passed to the constructor.

```php
$entity = $entityMapper->create(User::class, 'John', 'john@example.com');
```

### Convert data into entities

```php
use Jasny\EntityMapper\EntityMapper;

$entityMapper = new EntityMapper();

$data = [
  ['id' => 2, 'name' => 'John'],
  ['id' => 7, 'name' => 'Jane']
];

$entities = $entityMapper->convert(User::class, $data);
```

If no entities are supplied, the function will return a (callable) PipelineBuilder. This can be used as step within a
pipeline.

```php
use Improved\IteratorPipeline\Pipeline;

$entities = Pipeline::with($data)
  ->then($entityMapper->convert(User::class))
  ->toArray();
```

### Save entities to persistent storage

A callback needs to be provided which saves the data to the database (or other storage). It may return modified or
generated data (like auto-increment ids) as associative array per entity.

    iterable<array>|void callback(array[] $data)

The `SavePipeline` will handle converting the entity into data, calling triggers and apply the changes returned by
the callback.

```php
// Example to save to JSON file; normally you'd use a database
$persist = function(array $items) {
    foreach ($items as $i => $item) { 
        $id = preg_replace('/\W+/', '-', i\string_convert_case($item['name'], i\STRING_LOWERCASE));
        file_put_contents("items/$id.json", json_encode($item));
        
        yield $i => ['id' => $id];
    }
}

$entityMapper->save($persist, $entities); // Entities will have a new `id` property
```

It's possible to configure a custom pipeline for saving entities by passing a pipeline builder to `withSave()`. This
builder MUST have a `persist` stub.

### Delete entities from persistent storage

A callback needs to be provided which delete the data from the database (or other storage). An array with identities is
passed to the callback.

    void callback(array $ids)

The `DeletePipeline` will handle converting the entity into data, calling triggers and apply the changes returned by
the callback.

```php
// Example to delete JSON files; normally you'd use a database
$persist = function(array $ids) {
    foreach ($ids as $id) {
        if (!preg_match('/^[\w\-]+$/', $id) {
            throw new \UnexpectedValueException("Invalid id '$id'");
        }
        
        unlink("items/$id.json");
    }
}

$entityMapper->delete($persist, $entities);
```

It's possible to configure a custom pipeline for deleting entities by passing a pipeline builder to `withDelete()`. This
builder MUST have a `persist` stub.
