# Creating a Modal Class

The **Modal** class is the core of the Simp Modal system. It provides a structured way to define database tables, columns, primary keys, relationships, and CRUD operations.

This guide will walk you through creating custom modal classes using `UserTestModal` and `RoleTestModal` as examples.

---

## 1. Namespace & Inheritance

All modals extend the abstract `Modal` class:

```php
namespace Simp\Modal\Tests;

use Simp\Modal\Modal\Modal;

class UserTestModal extends Modal
{
    public function __construct(?\PDO $pdo = null)
    {
        parent::__construct($pdo);
        // ...
    }
}
```

* Modals can optionally accept a `PDO` instance for database connection.
* Always call `parent::__construct($pdo)` to initialize the base Modal class.

---

## 2. Set the Table Name

Define the table the modal maps to:

```php
$this->setTable('users');
```

* This tells the modal which table to perform CRUD operations on.

---

## 3. Define Columns

Columns define the schema of your table:

```php
$this->addColumn('id', 'INT', ['auto_increment' => true]);
$this->addColumn('name', 'VARCHAR(100)', ['nullable' => false]);
$this->addColumn('email', 'VARCHAR(150)', ['nullable' => false, 'unique' => true]);
$this->addColumn('password', 'VARCHAR(255)', ['nullable' => false]);
```

* Supported options: `auto_increment`, `nullable`, `unique`, `default`.
* Columns define the type and constraints for the database table.

---

## 4. Set Primary Key

```php
$this->setPrimaryKey('id');
```

* Primary key is required for updates, deletes, and relationships.

---

## 5. Enable Timestamps and Soft Deletes

```php
$this->modalDefinition['modal_timestamps'] = true;
$this->modalDefinition['modal_soft_deletes'] = true;
```

* `modal_timestamps` automatically manages `created_at` and `updated_at`.
* `modal_soft_deletes` adds `deleted_at` for soft deletion.

---

## 6. Column Updates (Optional)

```php
$this->addColumnUpdate('name', 'VARCHAR(200)', ['nullable' => false]);
$this->addColumnUpdate('status', 'ENUM("active", "inactive")', ['default' => 'active']);
```

* Update columns allow you to define schema modifications.
* Useful for migrations or extending the table dynamically.

---

## 7. Define Relationships

### UserTestModal: hasMany Roles

```php
$this->defineRelation('roles', function (array $userIds) {
    $roleModal = new RoleTestModal(self::$connection);
    $roles = $roleModal->whereIn('uid', $userIds)->get();

    $grouped = [];
    foreach ($roles as $role) {
        $grouped[$role['uid']][] = $role;
    }
    return $grouped;
});
```

* Each user can have many roles (`hasMany`).
* The `$callback` receives an array of user IDs and returns an array of roles grouped by `uid`.

---

### RoleTestModal: belongsTo User

```php
$this->defineRelation('user', function (array $roleUserIds) {
    $userModal = new UserTestModal(self::$connection);
    $users = $userModal->whereIn('id', $roleUserIds)->get();

    $map = [];
    foreach ($users as $user) {
        $map[$user['id']] = $user;
    }
    return $map;
});
```

* Each role belongs to a single user (`belongsTo`).
* The `$callback` receives an array of user IDs and returns a mapping of user data.

---

## 8. Usage Examples

### Initialize Modals

```php
$db = new PDO('mysql:host=localhost;dbname=test', 'root', '');
$userModal = UserTestModal::getModal($db);
$roleModal = RoleTestModal::getModal($db);
```

---

### Insert Users & Roles

```php
$aliceId = $userModal->fill(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => 'secret'])->insert();
$roleModal->fill(['role_name' => 'Admin', 'uid' => $aliceId])->insert();
```

---

### Fetch User with Roles (hasMany)

```php
$alice = $userModal->where('id', '=', $aliceId)->first();
$alice['roles'] = $roleModal->where('uid', '=', $alice['id'])->get();
```

---

### Fetch Role with User (belongsTo)

```php
$adminRole = $roleModal->where('role_name', '=', 'Admin')->first();
$adminRole['user'] = $userModal->where('id', '=', $adminRole['uid'])->first();
```

---

### Select Specific Columns

```php
$users = $userModal->select(['id', 'name'])->limit(5)->get();
```

---

### Complex Queries

```php
$users = $userModal
    ->whereIn('id', [$aliceId, $bobId])
    ->orWhere('name', '=', 'Charlie')
    ->select(['id', 'name', 'email'])
    ->orderBy('id', 'DESC')
    ->get();
```

* Attach hasMany roles for each user:

```php
foreach ($users as &$user) {
    $user['roles'] = $roleModal->where('uid', '=', $user['id'])->select(['role_name','uid'])->get();
}
```

---

### Update Example

```php
$userModal->where('id', '=', $aliceId)->fill(['name' => 'Alice Updated'])->update();
```

---

### Delete Example

```php
$roleModal->where('role_name', '=', 'Viewer')->delete();
```

---

This structure covers **creating modals**, **defining schema**, **setting relationships**, and **querying the database** with the Simp Modal system.

---