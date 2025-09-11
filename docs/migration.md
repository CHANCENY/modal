# Running Migrations with Simp Modal

The Simp Modal system provides a convenient **migration CLI** to create and update database tables directly from your modal definitions. This allows you to keep your database schema in sync with your modal classes.

---

## 1. Setup PDO Connection

Before running migrations, you need to establish a connection to your MySQL database using PDO:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

// Connect to MySQL
$db = new PDO('mysql:host=database;dbname=lamp', 'lamp', 'lamp');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
```

* Replace `database`, `lamp`, and credentials with your MySQL configuration.
* PDO is required for the migration CLI to execute queries.

---

## 2. Initialize Migration CLI

Create a migration CLI instance by providing the path to your modal classes:

```php
$cli = \Simp\Modal\ModalDefinitions\ModalMigrationCLI::create(__DIR__ . '/src/Tests');
```

* The path should point to the directory where your modal classes (e.g., `UserTestModal`, `RoleTestModal`) are located.
* The CLI will read all modal definitions to generate the necessary SQL for table creation and updates.

---

## 3. Run the Migration

Call the `createMigration` method to generate or update tables:

```php
$status = $cli->createMigration();
```

* This method executes the migration and returns an array of status messages for each table processed.

---

## 4. Output Migration Status

You can display the results in the terminal:

```php
echo PHP_EOL;
echo implode(PHP_EOL, $status);
echo PHP_EOL;
```

Example output:

```
Table `users` created successfully
Table `roles` created successfully
```

---

## 5. Complete Migration Script

Here’s the full example of a migration script (`migration.php`):

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$db = new PDO('mysql:host=database;dbname=lamp', 'lamp', 'lamp');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

try {
    $cli = \Simp\Modal\ModalDefinitions\ModalMigrationCLI::create(__DIR__ . '/src/Tests');
    $status = $cli->createMigration();
    echo PHP_EOL;
    echo implode(PHP_EOL, $status);
    echo PHP_EOL;
} catch (Throwable $e) {
    dump($e->getMessage(), $e->getFile(), $e->getLine());
}
```

---

## 6. Run the Migration

To run the migration, execute the script via PHP:

```bash
php migration.php
```

* The script will create or update all tables defined in your modal classes.
* Errors are caught and displayed for debugging.

---

## Notes

* Make sure your modal classes have **primary keys** defined (`setPrimaryKey`).
* Relationships such as `hasMany` or `belongsTo` are automatically handled if foreign keys are defined.
* Timestamps (`created_at`, `updated_at`) and soft deletes (`deleted_at`) are automatically included if enabled.

---

This migration system allows you to **automatically keep your database schema aligned with your modals**, removing the need for manual SQL scripts.

---