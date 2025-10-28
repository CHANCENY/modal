<?php

namespace Simp\Modal\ModalDefinitions;

use PDO;
use ReflectionException;
use Simp\Modal\Modal\Modal;

/**
 * Class ModalMigrationCLI
 *
 * Handles the configuration and management of modal migrations.
 */
class ModalMigrationCLI
{
    /**
     * @var ModalConfiguration $modalConfiguration
     */
    protected ModalConfiguration $modalConfiguration;

    /**
     * @var array $modalClasses
     */
    protected array $modalClasses;

    /**
     * @throws ReflectionException
     */
    public function __construct(ModalConfiguration $modalConfiguration)
    {
        $this->modalConfiguration = $modalConfiguration;
        $this->modalClasses = $modalConfiguration->getModals();
    }

    /**
     * Creates a new instance of ModalMigrationCLI using the specified modal directory.
     *
     * @param string $modal_directory The directory containing modal configurations.
     * @return ModalMigrationCLI A new instance of ModalMigrationCLI.
     * @throws ReflectionException
     */
    public static function create(string $modal_directory): ModalMigrationCLI
    {
        return new self(new ModalConfiguration($modal_directory));
    }

    /**
     * Creates migration files for each modal in the specified modal directory.
     * @return array
     */
    public function createMigration(): array
    {
        $migration_directory = $this->modalConfiguration->getModalDirectory() . DIRECTORY_SEPARATOR . 'Migrations';

        if (!is_dir($migration_directory)) {
            mkdir($migration_directory, 0755, true);
        }

        $statuses = [];

        foreach ($this->modalClasses as $modalClass) {

            if ($modalClass instanceof Modal && !empty($modalClass->getTable())) {

                $index = 1;
                $table = $modalClass->getTable();

                // Ensure unique migration filename
                $migration_file = "create_migration_for_{$table}_000{$index}.php";
                while (file_exists($migration_directory . DIRECTORY_SEPARATOR . $migration_file)) {
                    $index++;
                    $migration_file = "create_migration_for_{$table}_000{$index}.php";
                }
                $migration_file = $migration_directory . DIRECTORY_SEPARATOR . $migration_file;

                // Generate schema
                $create_schema = $modalClass->generateMigration();
                $alter_schema  = $modalClass->generateAlterMigration();
                $drop_schema   = $modalClass->generateDropMigration();

                // Determine namespace
                $existing_namespace = get_class($modalClass);
                $namespace = substr($existing_namespace, 0, strrpos($existing_namespace, '\\')) . "\\Migrations";

                // Sanitize class name for table
                $migrator_class = 'CreateMigrationFor' . str_replace(' ', '', ucwords(str_replace('_', ' ', $table))) . sprintf('%03d', $index);

                // Format arrays for better readability
                $create_schema_export = str_replace(["\n", "  "], ["\n\t\t", "\t"], var_export($create_schema, true));
                $alter_schema_export  = str_replace(["\n", "  "], ["\n\t\t", "\t"], var_export($alter_schema, true));
                $drop_schema_export   = str_replace(["\n", "  "], ["\n\t\t", "\t"], var_export($drop_schema, true));

                // Build migration content
                $content = "<?php\n\n";
                $content .= "namespace $namespace;\n\n";
                $content .= "class $migrator_class {\n\n";
                $content .= "\tpublic function up(): string\n\t{\n";
                $content .= "\t\treturn $create_schema_export;\n";
                $content .= "\t}\n\n";
                $content .= "\tpublic function modify(): array\n\t{\n";
                $content .= "\t\treturn $alter_schema_export;\n";
                $content .= "\t}\n\n";

                $content .= "\tpublic function getTable(): string\n\t{\n";
                $content .= "\t\treturn '$table';\n";
                $content .= "\t}\n\n";

                $content .= "\tpublic function down(): string\n\t{\n";
                $content .= "\t\treturn $drop_schema_export;\n";
                $content .= "\t}\n";
                $content .= "}\n";

                $content .= "return ".$migrator_class."::class;\n";

                $class_name = get_class($modalClass);
                // Save to file
                if (file_put_contents($migration_file, $content)) {
                    $statuses[] = "Migration for modal {$class_name} created successfully.";
                }
                else {
                    $statuses[] = "Failed to create migration for modal {$class_name}.";
                }
                $statuses[] = str_repeat('_', strlen(end($statuses)) + 20);
            }
        }
        return $statuses;
    }

    /**
     * Executes migration files located in the specified migrations directory.
     *
     * @param PDO $connection The database connection object used to execute the migration queries.
     * @return array An array containing the status of each migration operation.
     * @throws ReflectionException If there is an issue with reflection while handling the migration classes.
     */
    public function doMigrate(PDO $connection): array
    {
        $migrationDir = $this->modalConfiguration->getModalDirectory() . DIRECTORY_SEPARATOR . 'Migrations';
        $files = array_diff(scandir($migrationDir), ['..', '.']);
        if (empty($files)) {
            return [
                'No migration files found in the specified directory. Please create migration files first.',
                str_repeat('_', strlen('No migration files found in the specified directory. Please create migration files first.') + 20)
            ];
        }

        $status = [];

        foreach ($files as $file) {

            try{
                // Keep your line
                $className = require_once $migrationDir . DIRECTORY_SEPARATOR . $file;

                if (class_exists($className)) {
                    $instance = new $className();
                    $query = $instance->up();

                    $result = $connection->exec($query);

                    if ($result !== false) {
                        $status[] = "Migration for table {$instance->getTable()} was successful.";
                    } else {
                        $status[] = "Migration for table {$instance->getTable()} failed.";
                    }
                    $status[] = str_repeat('_', strlen(end($status)) + 20);
                } else {
                    $status[] = "Migration class {$className} not found in {$file}.";
                }
            }catch (\Throwable $e){
                $status[] = $e->getMessage();
                $status[] = str_repeat('_', strlen(end($status)) + 20);
            }
        }

        return $status;
    }

    /**
     * Executes migration files located in the specified migrations directory.
     * @param PDO $connection
     * @return array
     */
    public function doMigrateModify(PDO $connection): array
    {
        $migrationDir = $this->modalConfiguration->getModalDirectory() . DIRECTORY_SEPARATOR . 'Migrations';
        $files = array_diff(scandir($migrationDir), ['..', '.']);
        if (empty($files)) {
            return [
                'No migration files found in the specified directory. Please create migration files first.',
                str_repeat('_', strlen('No migration files found in the specified directory. Please create migration files first.') + 20)
            ];
        }

        $status = [];

        foreach ($files as $file) {

            try{
                // Keep your line
                $className = require $migrationDir . DIRECTORY_SEPARATOR . $file;

                if (class_exists($className)) {
                    $instance = new $className();
                    $queries = $instance->modify();

                    if (!empty($queries)) {

                        foreach ($queries as $q) {

                            try{
                                $result = $connection->exec($q);

                                if ($result !== false) {
                                    $status[] = "Migration modification for table {$instance->getTable()} was successful.";
                                } else {
                                    $status[] = "Migration modification for table {$instance->getTable()} failed.";
                                }
                                $status[] = str_repeat('_', strlen(end($status)) + 20);
                            }catch (\Throwable $e){
                                $status[] = $e->getMessage();
                                $status[] = str_repeat('_', strlen(end($status)) + 20);
                            }
                        }
                    }

                } else {
                    $status[] = "Migration class {$className} not found in {$file}.";
                }
            }catch (\Throwable $e){
                $status[] = $e->getMessage();
                $status[] = str_repeat('_', strlen(end($status)) + 20);
            }
        }
        return $status;
    }

    /**
     * Drops database tables based on migration files found in the specified migrations directory.
     *
     * @param PDO $connection The PDO connection instance used to execute SQL queries.
     * @return array An array of status messages indicating the result of each migration drop operation.
     */
    public function doDrop(PDO $connection): array
    {
        $migrationDir = $this->modalConfiguration->getModalDirectory() . DIRECTORY_SEPARATOR . 'Migrations';
        $files = array_diff(scandir($migrationDir), ['..', '.']);
        if (empty($files)) {
            return [
                'No migration files found in the specified directory. Please create migration files first.',
                str_repeat('_', strlen('No migration files found in the specified directory. Please create migration files first.') + 20)
            ];
        }

        $status = [];
        try{
            foreach ($files as $file) {

                // Keep your line
                $className = require_once $migrationDir . DIRECTORY_SEPARATOR . $file;

                if (class_exists($className)) {
                    $instance = new $className();
                    $query = $instance->down();
                    $result = $connection->exec($query);

                    if ($result !== false) {
                        $status[] = "Migration drop for table {$instance->getTable()} was successful.";
                    } else {
                        $status[] = "Migration drop for table {$instance->getTable()} failed.";
                    }
                    $status[] = str_repeat('_', strlen(end($status)) + 20);

                } else {
                    $status[] = "Migration class {$className} not found in {$file}.";
                }
            }
        }catch (\Throwable $e){
            $status[] = $e->getMessage();
            $status[] = str_repeat('_', strlen(end($status)) + 20);
        }

        return $status;
    }

}