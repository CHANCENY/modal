# Database Migration with Simp Modal

Simp Modal provides a migration CLI to create and update database tables directly from your modal definitions. This helps keep your database schema in sync with your modals.

---

## 1. Setup PDO Connection

Before running migrations, connect to your MySQL database using PDO:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

// Make PDO connection to MySQL
$db = new PDO('mysql:host=database;dbname=lamp', 'lamp', 'lamp');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
```

* Replace `database`, `lamp`, and credentials with your database configuration.
* PDO is required for the migration CLI to execute queries.

---

## 2. Initialize Migration CLI

Create a migration CLI instance, pointing to the folder where your modal classes are defined:

```php
$cli = \Simp\Modal\ModalDefinitions\ModalMigrationCLI::create(__DIR__ . '/src/Tests');
```

* The path should contain your modal classes (e.g., `UserTestModal`, `RoleTestModal`).
* The CLI reads modal definitions and generates SQL queries to create or update tables.

---

## 3. Run the Migration

Use the `doMigrate` method to run the migration:

```php
$status = $cli->doMigrate($db);
```

* This executes the migration on the connected database.
* The method returns an array of status messages for each table processed.

---

## 4. Display Migration Status

Output the migration results in the terminal:

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

Here’s a full example (`migrate.php`) to run migrations:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$db = new PDO('mysql:host=database;dbname=lamp', 'lamp', 'lamp');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

try {
    $cli = \Simp\Modal\ModalDefinitions\ModalMigrationCLI::create(__DIR__ . '/src/Tests');
    $status = $cli->doMigrate($db);
    echo PHP_EOL;
    echo implode(PHP_EOL, $status);
    echo PHP_EOL;
} catch (Throwable $e) {
    dump($e->getMessage(), $e->getFile(), $e->getLine());
}
```

---

## 6. Run the Migration

Execute the script via CLI:

```bash
php migrate.php
```

* Tables defined in your modal classes will be created or updated automatically.
* Any errors during migration are caught and displayed for debugging.

---

## Notes

* Ensure your modals have **primary keys** defined.
* Relationships (`hasMany`, `belongsTo`) are automatically respected if foreign keys are defined.
* Enabling **timestamps** (`created_at`, `updated_at`) and **soft deletes** (`deleted_at`) is optional but recommended.
* This process allows your database to stay synchronized with your modal definitions without manual SQL scripts.

---