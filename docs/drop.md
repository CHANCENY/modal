# Dropping Tables with Simp Modal

Simp Modal provides a simple way to drop tables defined in your modal classes. This is useful for cleaning up your database or resetting tables during development.

---

## 1. Setup PDO Connection

Connect to your MySQL database using PDO:

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

* The CLI reads modal definitions to determine which tables exist and need to be dropped.

---

## 3. Run the Drop Migration

Use the `doDrop` method to drop the tables:

```php
$status = $cli->doDrop($db);
```

* This will **drop all tables** corresponding to your modal definitions.
* Use this with caution—this operation is **destructive** and cannot be undone.

---

## 4. Display Drop Status

Output the results in the terminal:

```php
echo PHP_EOL;
echo implode(PHP_EOL, $status);
echo PHP_EOL;
```

Example output:

```
Table `users` dropped successfully
Table `roles` dropped successfully
```

---

## 5. Complete Drop Script

Here’s a full example (`drop.php`) to drop tables:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$db = new PDO('mysql:host=database;dbname=lamp', 'lamp', 'lamp');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

try {
    $cli = \Simp\Modal\ModalDefinitions\ModalMigrationCLI::create(__DIR__ . '/src/Tests');
    $status = $cli->doDrop($db);
    echo PHP_EOL;
    echo implode(PHP_EOL, $status);
    echo PHP_EOL;
} catch (Throwable $e) {
    dump($e->getMessage(), $e->getFile(), $e->getLine());
}
```

---

## 6. Run the Drop Migration

Execute the script via CLI:

```bash
php drop.php
```

* All tables defined in your modal classes will be dropped.
* Ensure you **backup your data** if needed before running this command.

---

## Notes

* Only tables defined in your modal classes folder (`src/Tests`) will be affected.
* Use `doDrop` primarily for development or testing environments.
* For production, it’s safer to use `doMigrateModify` or selective deletions.

---