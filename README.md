![jasny-banner](https://user-images.githubusercontent.com/100821/62123924-4c501c80-b2c9-11e9-9677-2ebc21d9b713.png)

Persist
===

[![Build Status](https://travis-ci.org/jasny/persist.svg?branch=master)](https://travis-ci.org/jasny/persist)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jasny/persist/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jasny/persist/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jasny/persist/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jasny/persist/?branch=master)
[![Packagist Stable Version](https://img.shields.io/packagist/v/jasny/persist.svg)](https://packagist.org/packages/jasny/persist)
[![Packagist License](https://img.shields.io/packagist/l/jasny/persist.svg)](https://packagist.org/packages/jasny/persist)

Object Relational Mapper (ORM) and Object Document Mapper that works with plain old PHP objects.

Installation
---

    composer require jasny/persist

Usage
---

```php
use App\User;
use Jasny\Persist\Gateway;
use Jasny\DB\Mongo\Read\MongoReader;
use Jasny\DB\Mongo\Write\MongoWriter;
use Jasny\DB\Search\NoSearch;

$mongoCollection = (new MongoClient)->getDB('test')->getCollection('users');
$read = new MongoReader($mongoCollection);
$write = new MongoWriter($mongoCollection);
$search = new NoSearch();

$dbUsers = new Gateway(User:class, $read, $write, $search);

$user = $dbUsers->fetch(123);
$user->email = 'foo@example.com';

$dbUsers->save($user);
```

### Before you continue

You don't need to use ORM for everything. It's a pretty heavy abstraction, so only apply it where it's meaningfull.

Typically it's easier to work with the database without ORM and faster too. The
[Jasny DB library](https://github.com/jasny/db) is a great database abstraction layer that allows you to stream and
convert data, even without ORM.

Documentation
---

### Plain old PHP object

The class can be any plain old PHP object. By default the data from the **public** properties are stored and
property names should match the fields in the DB table or collection.

```php
namespace App;

class User
{
    public ?int $id = null;
    public ?string $name = null;
    public string $type = 'client';
    public ?string $email = null;
    public string $status = 'unconfirmed';
}
```

#### Class constructor

If the class has a constructor, it will be called **after** the properties are set. Constructor arguments must be
optional.

```php
namespace App;

class User
{
    public ?int $id;
    public ?string $name;
    public string $type;
    public ?string $email;
    public string $status = 'unconfirmed';

    public function __construct(string $type = 'client')
    {
        $this->type ??= $type;

        if ($this->id !== null && $this->email === null) {
            $this->status = 'unknown';
        }
    }
}
```

#### Set state

If the class has a `__set_state()` method it will be called when creating an object from existing data. In that case,
the constructor will not be called. The `__set_state()` method needs to take care of that.

```php
namespace App;

use function Jasny\object_set_properties;

class User
{
    public ?int $id;
    public ?string $email;
    public ?string $password;
    
    protected ?string $hashedPassword;

    public function __construct(string $type = 'client')
    {
        // ...
    }

    public static function __set_state(array $values): self
    {
        $user->hashedPassword = $values['password'] ?? null;
        unset($values['password']);

        $user = (new \ReflectionClass(get_called_class()))->newInstanceWithoutConstructor();
        object_set_properties($user, $values);

        $user->__construct();

        return $user;
    }
}
```

### Gateway

`Gateway` is the main class of the library. Other classes are intended for customization.

    new Gateway(
        string $class,
        Jasny\DB\Read $read,
        Jasny\DB\Write $write,
        [array $options]
    )

The read and write service come from one of the [Jasny DB](https://github.com/jasny/db) libraries. These services do
the heavy lifting of fetching the data from the database.

#### Create a new object

The gateway can be used as factory to create a new object.

```php
$user = $dbUsers->create();
```

Arguments will be passed to the constructor.

```php
$user = $dbUsers->create('admin');
```

Use the `create()` method rather than just `new User()` to allow mocking entities in unit tests. It also sets the event
dispatcher if supported.

#### Find an object

Fetch a record/document from the data and use it to create an object.

```php
$user = $dbUsers->findOne($id);
```

Instead of the id value, a [_(Jasny DB)_ filter](https://github.com/jasny/db#filters) may be specified. Only the first
match is used.

```php
$user = $dbUsers->findOne(['email' => 'john@example.com', 'status(not)' => 'banned']);
```

If no record is found, `findOne()` will throw a `NotFoundException`.

##### Optional find

The `findFirst()` method will return `null` if no record is found, instead of throwing an exception.

```php
$user = $dbUsers->findFirst($id);

if ($user === null) {
    // ...
}
```

_Having two separate methods for finding an object helps with [static code analysis](https://github.com/phpstan/phpstan)._

#### Find all objects

Load multiple objects using a [_(Jasny DB)_ filter](https://github.com/jasny/db#filters);

```php
$user = $dbUsers->findAll(['type' => 'admin']);
```

#### Check if an object exists

Check if an object with in storage by id;

```php
if ($dbUsers->exists($id)) {
    //...
}
```

Alternatively you can use `exists()` to see if any object exists that satisfies the filter;

```php
if ($dbUsers->exists(['email' => 'jane@example.com'])) {
    //...
}
```

#### Check if an object has a unique property

Check if an object has a unique property.

```php
if (!$dbUsers->hasUnique($user, 'email')) {
    //...
}
```

It will check if any user has a different id but the same email address.

Optionally you can specify fields that need to match.

```php
if (!$dbUsers->hasUnique($user, 'email', ['organization'])) {
    //...
}
```

#### Save objects

Create or update a record in the DB;

```php
$dbUsers->save($user);
```

You can also create or update multiple records at once. In that case pass any iterable (like an array or `Generator`).
If possible, the objects will be saved in a single query.

```php
$dbUser->save($users);
```

#### Delete objects

Delete a record in the DB;

```php
$dbUsers->delete($user);
```

Similar to `update()`, you can delete multiple records at once by passing an iterable.

```php
$dbUser->delete($users);
```

#### Query options

You can pass [_(Jasny DB)_ options](https://github.com/jasny/db#options) as second argument to all of the Gateway
methods. These options can be used for sorting, limiting, etc and are passed to the underlying read or write service.

In addition to the generic options, most DB libraries have options that are specific to that database.

```php
use Jasny\DB\Option as opt;
use Jasny\DB\Mongo\Option as mongo_opt;

$user = $dbUsers->findAll(['type' => 'admin'], [opt\limit(10), opt\sort('name'), mongo_opt\hint('type_index')]);
```

