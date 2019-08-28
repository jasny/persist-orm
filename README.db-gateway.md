Jasny DB Gateway
===

[![Build Status](https://travis-ci.org/jasny/db-gateway.svg?branch=master)](https://travis-ci.org/jasny/db-gateway)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jasny/db-gateway/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jasny/db-gateway/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jasny/db-gateway/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jasny/db-gateway/?branch=master)
[![Packagist Stable Version](https://img.shields.io/packagist/v/jasny/db-gateway.svg)](https://packagist.org/packages/jasny/db-gateway)
[![Packagist License](https://img.shields.io/packagist/l/jasny/db-gateway.svg)](https://packagist.org/packages/jasny/db-gateway)

Table/Collection data dateway using

* [Jasny DB](https://github.com/jasny/db)
* [Jasny Entity](https://github.com/jasny/entity)
* [Jasny Entity Mapper](https://github.com/jasny/entity-mapper)
* [Jasny Event Handler](https://github.com/jasny/event-handler)

Installation
---

    composer require jasny/db-gateway

Usage
---

```php
use Jasny\DBGateway\Gateway;
use Jasny\EntityMapper\EntityMapper;
use Jasny\DB\Mongo\CRUD\CRUD;
use Jasny\DB\Search\NoSearch;

// Services (typically get these from a container)
$db = (new MongoClient)->test;
$mapper = new EntityMapper();
$crud = new CRUD();
$search = new NoSearch();

$users = new Gateway(User:class, $db->users, $mapper, $crud, $search);

$user = $users->find(123);
$user->set('email', 'foo@example.com');

$users->save($user);
```

