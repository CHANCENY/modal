# Modifying Existing Tables with Simp Modal

Simp Modal allows you to modify existing tables in your database whenever you update your modal definitions. This ensures your database stays in sync with your modals without manually writing `ALTER TABLE` queries.

---

## 1. Setup PDO Connection

Before running table modifications, connect to your MySQL database using PDO:

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

---

## 2. Initialize Migration CLI

Create a migration CLI instance, pointing to your modal classes folder:

```php
$cli = \Simp\Modal\ModalDefinitions\ModalMigrationCLI::create(__DIR__ . '/src/Tests');
```

* The CLI reads modal definitions and generates SQL queries to **modify existing tables** according to changes in columns, types, or constraints.

---

## 3. Run the Modify Migration

Use the `doMigrateModify` method to apply modifications:

```php
$status = $cli->doMigrateModify($db);
```

* This method checks each table against its modal definition.
* Columns that were added, updated, or changed in your modal class will be updated in the database table automatically.

---

## 4. Display Modification Status

Output the results in the terminal:

```php
echo PHP_EOL;
echo implode(PHP_EOL, $status);
echo PHP_EOL;
```

Example output:

```
Table `users` modified successfully
Table `roles` modified successfully
```

---

## 5. Complete Modification Script

Here’s a full example (`modify.php`) to modify existing tables:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$db = new PDO('mysql:host=database;dbname=lamp', 'lamp', 'lamp');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

try {
    $cli = \Simp\Modal\ModalDefinitions\ModalMigrationCLI::create(__DIR__ . '/src/Tests');
    $status = $cli->doMigrateModify($db);
    echo PHP_EOL;
    echo implode(PHP_EOL, $status);
    echo PHP_EOL;
} catch (Throwable $e) {
    dump($e->getMessage(), $e->getFile(), $e->getLine());
}
```

---

## 6. Run the Modify Migration

Execute the script via CLI:

```bash
php modify.php
```

* Existing tables will be updated to match the latest modal definitions.
* New columns, column updates, and changes in constraints will be applied automatically.
* Any errors during modification are caught and displayed for debugging.

---

## Notes

* Make sure all modal definitions are updated with the new column definitions using `addColumnUpdate`.
* Soft deletes and timestamps are respected during modifications.
* This process avoids manually running `ALTER TABLE` commands for every change in your application.

---