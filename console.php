<?php

use Simp\Modal\ModalDefinitions\ModalMigrationCLI;

require_once __DIR__ . '/vendor/autoload.php';

// Get arg passed to the console
$arg = $argv[1] ?? null;

// Database connection
$db = new PDO('mysql:host=database;dbname=lamp', 'lamp', 'lamp');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

// Configure model directory
$cli = ModalMigrationCLI::create(__DIR__ . '/src/Tests');

/**
 * We have five options commands
 *
 * 1. php console.php modal:migration
 * 2. php console.php modal:migrate
 * 3. php console.php modal:modify
 * 4. php console.php modal:drop
 * 5. php console.php modal:generate
 */

$options_handlers = array(
    'modal:migration' => function () use ($db, $cli) {
        return $cli->createMigration();
    },
    'modal:migrate' => function () use ($db, $cli) {
        return $cli->doMigrate($db);
    },
    'modal:modify' => function () use ($db, $cli) {
        return $cli->doMigrateModify($db);
    },
    'modal:drop' => function () use($db, $cli) {
        return $cli->doDrop($db);
    },
    'modal:generate' => function () use($db, $cli) {
        // do something here
    }
);

$callback = $options_handlers[$arg] ?? null;

if ($callback) {
    $result = $callback();
    foreach ($result as $line) {

        echo $line . PHP_EOL;
    }
} else {
    echo "Invalid option";
}