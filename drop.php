<?php

require_once __DIR__ . '/vendor/autoload.php';

// make pdo connection to myql database
$db = new PDO('mysql:host=database;dbname=lamp', 'lamp', 'lamp');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);


try {
    $cli = \Simp\Modal\ModalDefinitions\ModalMigrationCLI::create(__DIR__ . '/src/Tests');
    //$status = $cli->createMigration();
    $status = $cli->doDrop($db);
    echo PHP_EOL;
    echo implode(PHP_EOL, $status);
    echo PHP_EOL;
} catch (Throwable $e) {
    dump($e->getMessage(), $e->getFile(), $e->getLine());
}