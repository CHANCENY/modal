# Simp Modal

This project demonstrates a lightweight PHP Modal system with **CRUD operations, relations, and query building**.

## Table of Contents

* [Installation](#installation)
* [Initializing Modals](docs/modal.md)
* [Inserting Records](docs/modal.md#inserting-records)
* [Selecting Specific Columns](docs/modal.md#selecting-specific-columns)
* [Relations](docs/modal.md#relations)
    * [User ↔ Role Relations](docs/modal.md#user-↔-role-relations)
* [Usage Examples](docs/modal.md#usage-examples)
    * [Has One Relation (User -> Role)](docs/modal.md#has-one-relation-user--role)
    * [Has Many Relation (User -> Roles)](docs/modal.md#has-many-relation-user--roles)
    * [Belongs To Relation (Role -> User)](docs/modal.md#belongs-to-relation-role--user)
* [Complex Query Example](docs/modal.md#complex-query-example)
* [Update Example](docs/modal.md#update-example)
* [Delete Example](docs/modal.md#delete-example)
* [Migrations](docs/migration.md)
    * [Create Migration](docs/migration.md#create-migration)
    * [Run Migrate](docs/migrate.md#run-migrate)
    * [Modify Migration](docs/modify.md#modify-migration)
    * [Drop Tables](docs/drop.md#drop-tables)
* [Notes](#notes)

---

## Installation

```bash
composer require simp/modal
```

Make sure your database is running and accessible. Update the database connection settings in `example.php`:

```php
$db = new PDO('mysql:host=database;dbname=lamp', 'lamp', 'lamp');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
```

---

## Initializing Modals

```php
$userModal = UserTestModal::getModal($db);
$roleModal = RoleTestModal::getModal($db);
```

Clear tables before seeding:

```php
$db->exec("DELETE FROM {$roleModal->getTable()}");
$db->exec("ALTER TABLE {$roleModal->getTable()} AUTO_INCREMENT = 1");
$db->exec("DELETE FROM {$userModal->getTable()}");
$db->exec("ALTER TABLE {$userModal->getTable()} AUTO_INCREMENT = 1");
```

---

## Inserting Records

```php
$aliceId   = $userModal->fill(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => 'secret'])->insert();
$bobId     = $userModal->fill(['name' => 'Bob', 'email' => 'bob@example.com', 'password' => 'pass'])->insert();
$charlieId = $userModal->fill(['name' => 'Charlie', 'email' => 'charlie@example.com', 'password' => '1234'])->insert();

$roleModal->fill(['role_name' => 'Admin', 'uid' => $aliceId])->insert();
$roleModal->fill(['role_name' => 'Editor', 'uid' => $bobId])->insert();
$roleModal->fill(['role_name' => 'Subscriber', 'uid' => $bobId])->insert();
$roleModal->fill(['role_name' => 'Viewer', 'uid' => $charlieId])->insert();
```

---

## Selecting Specific Columns

```php
$usersSelected = $userModal
    ->select(['id', 'name'])
    ->limit(3)
    ->orderBy('id', 'ASC')
    ->get();
```

---

## Relations

### User ↔ Role Relations

```
User -> hasOne Role
User -> hasMany Roles
Role -> belongsTo User
```

#### Diagram

```text
+-----------------+        hasOne        +-----------------+
|      User       |--------------------->|       Role      |
|-----------------|                      |-----------------|
| id              |                      | id              |
| name            |                      | role_name       |
| email           |                      | uid (user_id)   |
| password        |                      | description     |
+-----------------+                      +-----------------+
       | 1
       | hasMany
       v
+-----------------+
|      Role       |
|-----------------|
| id              |
| role_name       |
| uid (user_id)   |
| description     |
+-----------------+
```

---

## Usage Examples

### Has One Relation (User -> Role)

```php
$alice = $userModal->where('id', '=', $aliceId)->first();
$aliceRole = $roleModal->where('uid', '=', $alice['id'])
                       ->select(['role_name', 'uid'])
                       ->first();
```

### Has Many Relation (User -> Roles)

```php
$bob = $userModal->where('id', '=', $bobId)->first();
$bobRoles = $roleModal->where('uid', '=', $bob['id'])
                      ->select(['role_name','uid'])
                      ->get();
```

### Belongs To Relation (Role -> User)

```php
$editorRole = $roleModal->where('role_name', '=', 'Editor')->first();
$editorUser = $userModal->where('id', '=', $editorRole['uid'])
                        ->select(['name', 'email'])
                        ->first();
```

---

## Complex Query Example

```php
$complexUsers = $userModal
    ->whereIn('id', [$aliceId, $bobId])
    ->orWhere('name', '=', 'Charlie')
    ->select(['id', 'name', 'email'])
    ->orderBy('id', 'DESC')
    ->get();

// Attach hasMany roles for each user
foreach ($complexUsers as &$user) {
    $user['roles'] = $roleModal->where('uid', '=', $user['id'])
                               ->select(['role_name','uid'])
                               ->get();
}
```

---

## Update Example

```php
$updateCount = $userModal->where('id', '=', $aliceId)
                         ->fill(['name' => 'Alice Updated'])
                         ->update();

$updatedUser = $userModal->where('id', '=', $aliceId)->first();
```

---

## Delete Example

```php
$deleteCount = $roleModal->where('role_name', '=', 'Viewer')->delete();
$remainingRoles = $roleModal->get();
```

---

## Notes

* **Select**: Choose specific columns with `select()`.
* **Where In / Or Where**: Build complex queries with `whereIn()` and `orWhere()`.
* **Relations**: Use `hasOne`, `hasMany`, and `belongsTo` to link modals.
* **Query Reset**: Each query resets automatically after execution.
* **Mass Assignment**: Use `fill()` for safe attribute assignment.

---
