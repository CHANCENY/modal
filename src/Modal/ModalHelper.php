<?php

namespace Simp\Modal\Modal;

use InvalidArgumentException;
use PDO;

/**
 * Trait ModalHelper
 *
 * Provides utilities for defining and managing modal definitions, including table structure,
 * columns, primary keys, foreign keys, as well as generating SQL migrations.
 */

trait ModalHelper
{
    protected static ?PDO $connection = null;
    
    /**
     * Defines the structure and metadata for a database modal.
     *
     * - `modal_table`: Specifies the name of the database table.
     * - `modal_columns`: An array defining the columns in the table.
     * - `modal_primary_key`: Specifies the primary key column for the table.
     * - `modal_indexes`: An array of indexes to be created on the table.
     * - `modal_timestamps`: Boolean indicating whether to include timestamp columns.
     * - `modal_soft_deletes`: Boolean indicating if soft delete functionality is enabled.
     * - `modal_fillable`: An array of columns that can be mass assignable.
     * - `modal_guarded`: An array of columns that are not mass assignable.
     * - `modal_hidden`: An array of columns that should be hidden in outputs.
     * - `modal_foreign_keys`: An array to define foreign key relationships.
     * - `modal_on_delete`: Specifies the behavior for deleting related records (e.g., CASCADE).
     * - `modal_on_update`: Specifies the behavior for updating related records.
     * - `modal_table_prefix`: Specifies a prefix string for the table name.
     * - `modal_table_suffix`: Specifies a suffix string for the table name.
     * - `modal_table_comment`: A comment or description for the table.
     * - `modal_table_auto_increment`: Specifies the auto-increment value for the primary key.
     * - `modal_table_auto_increment_start`: Specifies the starting value for auto-increment.
     */
    protected array $modalDefinition = [
        'modal_table' => '',
        'modal_columns' => [],
        'modal_primary_key' => '',
        'modal_indexes' => [],
        'modal_timestamps' => false,
        'modal_soft_deletes' => false,
        'modal_fillable' => [],
        'modal_guarded' => [],
        'modal_hidden' => [],
        'modal_foreign_keys' => [],
        'modal_on_delete' => 'CASCADE',
        'modal_on_update' => 'CASCADE',
        'modal_table_prefix' => '',
        'modal_table_suffix' => '',
        'modal_table_comment' => '',
        'modal_table_auto_increment' => 1,
        'modal_table_auto_increment_start' => 1,
    ];

    /**
     * Defines an updated structure and metadata for a database model.
     *
     * - `modal_table`: Represents the name of the associated database table.
     * - `modal_columns`: Contains a list of columns for the table configuration.
     * - `modal_primary_key`: Identifies the primary key field for the table.
     * - `modal_indexes`: Includes the indexes to be created for the table.
     * - `modal_timestamps`: Flag to indicate whether timestamp fields are enabled.
     * - `modal_soft_deletes`: Flag to control whether soft deletes are supported.
     * - `modal_fillable`: Specifies columns that can be directly assigned values.
     * - `modal_guarded`: Lists columns that cannot be mass assigned.
     * - `modal_hidden`: Specifies columns that will be excluded from the output.
     * - `modal_foreign_keys`: Maps relationships to other tables through foreign keys.
     * - `modal_on_delete`: Defines the behavior on related records' delete operations.
     * - `modal_on_update`: Defines the behavior on related records' update operations.
     * - `modal_table_prefix`: Adds a prefix to the table name if defined.
     * - `modal_table_suffix`: Adds a suffix to the table name if defined.
     * - `modal_table_comment`: Contains comments or a description for the table.
     * - `modal_table_auto_increment`: Sets the default auto-increment value.
     * - `modal_table_auto_increment_start`: Specifies the starting increment value.
     */
    protected array $modalDefinitionUpdate = [
        'modal_table' => '',
        'modal_columns' => [],
        'modal_primary_key' => '',
        'modal_indexes' => [],
        'modal_timestamps' => false,
        'modal_soft_deletes' => false,
        'modal_fillable' => [],
        'modal_guarded' => [],
        'modal_hidden' => [],
        'modal_foreign_keys' => [],
        'modal_on_delete' => 'CASCADE',
        'modal_on_update' => 'CASCADE',
        'modal_table_prefix' => '',
        'modal_table_suffix' => '',
        'modal_table_comment' => '',
        'modal_table_auto_increment' => 1,
        'modal_table_auto_increment_start' => 1,
    ];

    // ----------------------------
    // Basic setters
    // ----------------------------

    /**
     * Sets the table name for the modal definition by combining the table prefix, the provided table name, and the table suffix.
     *
     * @param string $table The name of the table to set.
     * @return void
     */
    public function setTable(string $table): void
    {
        $this->modalDefinition['modal_table'] =
            $this->modalDefinition['modal_table_prefix'] . $table . $this->modalDefinition['modal_table_suffix'];
    }

    /**
     * Sets the table name for the modal definition update, including prefix and suffix.
     *
     * @param string $table The base table name to be used in the modal definition update.
     * @return void
     */
    public function setTableUpdate(string $table): void
    {
        $this->modalDefinitionUpdate['modal_table'] =
            $this->modalDefinitionUpdate['modal_table_prefix'] . $table . $this->modalDefinitionUpdate['modal_table_suffix'];
    }

    /**
     * Adds a column definition to the modal definition.
     *
     * @param string $name The name of the column to be added.
     * @param string $type The data type of the column (e.g., 'int', 'varchar').
     * @param array $options Additional options for the column, such as 'nullable', 'default', 'unique', etc.
     * @return void
     */
    public function addColumn(string $name, string $type, array $options = []): void
    {
        $this->modalDefinition['modal_columns'][$name] = array_merge([
            'type' => strtoupper($type),
            'nullable' => false,
            'default' => null,
            'auto_increment' => false,
            'unique' => false,
        ], $options);
    }

    /**
     * Adds or updates a column definition to the modal definition update.
     *
     * @param string $name The name of the column to add or update.
     * @param string $type The data type of the column.
     * @param array $options Optional configurations for the column such as nullable, default value, auto-increment, or unique.
     * @return void
     */
    public function addColumnUpdate(string $name, string $type, array $options = []): void
    {
        $this->modalDefinitionUpdate['modal_columns'][$name] = array_merge([
            'type' => strtoupper($type),
            'nullable' => false,
            'default' => null,
            'auto_increment' => false,
            'unique' => false,
        ], $options);
    }

    /**
     * Sets the primary key for the modal definition.
     *
     * @param string $column The name of the column to set as the primary key.
     * @return void
     */
    public function setPrimaryKey(string $column): void
    {
        $this->modalDefinition['modal_primary_key'] = $column;

        // Ensure the primary key is guarded
        if (!in_array($column, $this->modalDefinition['modal_guarded'])) {
            $this->modalDefinition['modal_guarded'][] = $column;
        }
    }

    /**
     * Sets the primary key column for the modal definition update.
     *
     * @param string $column The name of the column to set as the primary key.
     * @return void
     */
    public function setPrimaryKeyUpdate(string $column): void
    {
        $this->modalDefinitionUpdate['modal_primary_key'] = $column;

        // Ensure the primary key is guarded
        if (!in_array($column, $this->modalDefinitionUpdate['modal_guarded'])) {
            $this->modalDefinitionUpdate['modal_guarded'][] = $column;
        }
    }

    // ----------------------------
    // Foreign keys
    // ----------------------------

    /**
     * Adds a foreign key constraint to the modal definition.
     *
     * @param string $column The column in the current table to reference.
     * @param string $refModalClass The fully qualified class name of the referenced modal.
     *                              Must extend \Simp\Modal\Modal\Modal.
     * @param string $refColumn The column in the referenced table to which the current table's column will link.
     * @param string $onDelete The action to take on delete operations. Defaults to 'CASCADE'.
     * @param string $onUpdate The action to take on update operations. Defaults to 'CASCADE'.
     *
     * @return static Returns the current instance for method chaining.
     *
     * @throws InvalidArgumentException If the provided $refModalClass does not extend \Simp\Modal\Modal\Modal.
     */
    public function addForeignKey(string $column, string $refModalClass, string $refColumn, string $onDelete = 'CASCADE', string $onUpdate = 'CASCADE'): static
    {
        if (!is_subclass_of($refModalClass, \Simp\Modal\Modal\Modal::class)) {
            throw new InvalidArgumentException("$refModalClass must extend " . \Simp\Modal\Modal\Modal::class);
        }

        $refTable = (new $refModalClass(self::$connection))->getTable();

        $this->modalDefinition['modal_foreign_keys'][] = [
            'column' => $column,
            'refTable' => $refTable,
            'refColumn' => $refColumn,
            'onDelete' => strtoupper($onDelete),
            'onUpdate' => strtoupper($onUpdate),
            'model' => $refModalClass,
        ];

        return $this;
    }

    /**
     * Adds a foreign key constraint to the modal definition for updates.
     *
     * @param string $column The column in the current table to reference.
     * @param string $refModalClass The fully qualified class name of the referenced modal.
     *                              Must extend \Simp\Modal\Modal\Modal.
     * @param string $refColumn The column in the referenced table to which the current table's column will link.
     * @param string $onDelete The action to take on delete operations. Defaults to 'CASCADE'.
     * @param string $onUpdate The action to take on update operations. Defaults to 'CASCADE'.
     *
     * @return static Returns the current instance for method chaining.
     *
     * @throws InvalidArgumentException If the provided $refModalClass does not extend \Simp\Modal\Modal\Modal.
     */
    public function addForeignKeyUpdate(string $column, string $refModalClass, string $refColumn, string $onDelete = 'CASCADE', string $onUpdate = 'CASCADE'): static
    {
        if (!is_subclass_of($refModalClass, \Simp\Modal\Modal\Modal::class)) {
            throw new InvalidArgumentException("$refModalClass must extend " . \Simp\Modal\Modal\Modal::class);
        }

        $refTable = (new $refModalClass(self::$connection))->getTable();

        $this->modalDefinitionUpdate['modal_foreign_keys'][] = [
            'column' => $column,
            'refTable' => $refTable,
            'refColumn' => $refColumn,
            'onDelete' => strtoupper($onDelete),
            'onUpdate' => strtoupper($onUpdate),
            'model' => $refModalClass,
        ];

        return $this;
    }

    // ----------------------------
    // Helper for FK names
    // ----------------------------

    /**
     * Generates a foreign key name based on the provided table and column names.
     *
     * @param array $fk An associative array containing the foreign key details, specifically the column name.
     * @return string The generated foreign key name.
     */
    protected function generateForeignKeyName(array $fk): string
    {
        $table = $this->modalDefinition['modal_table'] ?: $this->modalDefinitionUpdate['modal_table'];
        return "fk_{$table}_{$fk['column']}";
    }

    // ----------------------------
    // Automatic timestamps & soft deletes
    // ----------------------------

    /**
     * Extracts additional columns based on the provided table definition, such as timestamps or soft delete indicators.
     *
     * @param array $definition An associative array containing the table definition attributes, including optional flags like 'modal_timestamps' and 'modal_soft_deletes'.
     * @return array An associative array of extra columns, each defined with attributes such as type, nullability, default values, and uniqueness.
     */
    protected function getExtraColumns(array $definition): array
    {
        $extras = [];

        if (!empty($definition['modal_timestamps'])) {
            $extras['created_at'] = [
                'type' => 'DATETIME',
                'nullable' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'auto_increment' => false,
                'unique' => false,
            ];
            $extras['updated_at'] = [
                'type' => 'DATETIME',
                'nullable' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'auto_increment' => false,
                'unique' => false,
            ];
        }

        if (!empty($definition['modal_soft_deletes'])) {
            $extras['deleted_at'] = [
                'type' => 'DATETIME',
                'nullable' => true,
                'default' => null,
                'auto_increment' => false,
                'unique' => false,
            ];
        }

        return $extras;
    }

    // ----------------------------
    // Generate CREATE TABLE SQL
    // ----------------------------

    /**
     * Generates a SQL migration statement for creating a database table based on the modal definition.
     *
     * @return string A SQL string for creating the database table, including columns, primary keys, unique keys, indexes,
     *                foreign key constraints, and other table-level configurations.
     */
    public function generateMigration(): string
    {
        $table = $this->modalDefinition['modal_table'];
        $columnsSql = [];

        $allColumns = array_merge($this->modalDefinition['modal_columns'], $this->getExtraColumns($this->modalDefinition));

        foreach ($allColumns as $name => $col) {
            $colDef = "`$name` {$col['type']}";
            if (!$col['nullable']) $colDef .= " NOT NULL";
            if ($col['auto_increment']) $colDef .= " AUTO_INCREMENT";
            if (!is_null($col['default'])) {
                $default = is_numeric($col['default']) || strtoupper($col['default']) === 'CURRENT_TIMESTAMP'
                    ? $col['default']
                    : "'{$col['default']}'";
                $colDef .= " DEFAULT {$default}";
            }
            if ($col['unique']) $colDef .= " UNIQUE";
            $columnsSql[] = $colDef;
        }

        if (!empty($this->modalDefinition['modal_primary_key'])) {
            $columnsSql[] = "PRIMARY KEY (`{$this->modalDefinition['modal_primary_key']}`)";
        }

        foreach ($this->modalDefinition['modal_indexes'] as $index) {
            $cols = implode("`,`", $index['columns']);
            $columnsSql[] = "{$index['type']} (`{$cols}`)";
        }

        foreach ($this->modalDefinition['modal_foreign_keys'] as $fk) {
            $fkName = $this->generateForeignKeyName($fk);
            $columnsSql[] = "CONSTRAINT `{$fkName}` FOREIGN KEY (`{$fk['column']}`)
                             REFERENCES `{$fk['refTable']}`(`{$fk['refColumn']}`)
                             ON DELETE {$fk['onDelete']}
                             ON UPDATE {$fk['onUpdate']}";
        }

        $sql = "CREATE TABLE `{$table}` (\n  " . implode(",\n  ", $columnsSql) . "\n)";
        if (!empty($this->modalDefinition['modal_table_comment'])) {
            $sql .= " COMMENT='{$this->modalDefinition['modal_table_comment']}'";
        }
        $sql .= " AUTO_INCREMENT={$this->modalDefinition['modal_table_auto_increment_start']};";

        return $sql;
    }

    // ----------------------------
    // Generate ALTER TABLE SQL
    // ----------------------------

    /**
     * Generates a series of SQL ALTER statements based on the differences between the current and updated modal definitions.
     * The statements cover changes to columns, primary keys, indexes, and foreign keys within a database table.
     *
     * @return array An array of SQL ALTER statements reflecting the changes needed to migrate from the current modal definition to the updated modal definition.
     */
    public function generateAlterMigration(): array
    {
        $table = $this->modalDefinition['modal_table'];
        $alterStatements = [];

        // Merge normal columns and extra columns (timestamps, soft deletes)
        $oldCols = array_merge($this->modalDefinition['modal_columns'], $this->getExtraColumns($this->modalDefinition));
        $newCols = array_merge($this->modalDefinitionUpdate['modal_columns'], $this->getExtraColumns($this->modalDefinitionUpdate));

        // Add or modify columns
        foreach ($newCols as $name => $col) {
            $colDef = "`$name` {$col['type']}";
            if (!empty($col['nullable']) === false) $colDef .= " NOT NULL";
            if (!empty($col['auto_increment'])) $colDef .= " AUTO_INCREMENT";
            if (isset($col['default'])) {
                $default = is_numeric($col['default']) || strtoupper($col['default']) === 'CURRENT_TIMESTAMP'
                    ? $col['default']
                    : "'{$col['default']}'";
                $colDef .= " DEFAULT {$default}";
            }
            if (!empty($col['unique'])) $colDef .= " UNIQUE";

            if (!isset($oldCols[$name])) {
                $alterStatements[] = "ALTER TABLE `{$table}` ADD COLUMN {$colDef};";
            } elseif ($oldCols[$name] != $col) {
                $alterStatements[] = "ALTER TABLE `{$table}` MODIFY COLUMN {$colDef};";
            }
        }

        // Primary key changes
        $oldPK = $this->modalDefinition['modal_primary_key'];
        $newPK = $this->modalDefinitionUpdate['modal_primary_key'];

        if ($oldPK !== $newPK && !empty($newPK)) {
            if (!empty($oldPK)) {
                // Drop the old primary key only if it exists
                $alterStatements[] = "ALTER TABLE `{$table}` DROP PRIMARY KEY;";
            }
            // Add the new primary key
            $alterStatements[] = "ALTER TABLE `{$table}` ADD PRIMARY KEY (`{$newPK}`);";
        }

        // Indexes diff
        $oldIndexes = $this->modalDefinition['modal_indexes'];
        $newIndexes = $this->modalDefinitionUpdate['modal_indexes'];

        // Drop removed indexes
        foreach ($oldIndexes as $oldIndex) {
            $found = false;
            foreach ($newIndexes as $newIndex) {
                if ($oldIndex['type'] === $newIndex['type'] && $oldIndex['columns'] === $newIndex['columns']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $cols = implode("_", $oldIndex['columns']);
                $alterStatements[] = "ALTER TABLE `{$table}` DROP INDEX `{$cols}`;";
            }
        }

        // Add new indexes
        foreach ($newIndexes as $newIndex) {
            $found = false;
            foreach ($oldIndexes as $oldIndex) {
                if ($oldIndex['type'] === $newIndex['type'] && $oldIndex['columns'] === $newIndex['columns']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $cols = implode("`,`", $newIndex['columns']);
                $alterStatements[] = "ALTER TABLE `{$table}` ADD {$newIndex['type']} (`{$cols}`);";
            }
        }

        // Foreign keys diff
        $oldFKs = $this->modalDefinition['modal_foreign_keys'];
        $newFKs = $this->modalDefinitionUpdate['modal_foreign_keys'];

        // Drop removed FKs
        foreach ($oldFKs as $oldFK) {
            $fkName = $this->generateForeignKeyName($oldFK);
            $found = false;
            foreach ($newFKs as $newFK) {
                if ($oldFK == $newFK) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $alterStatements[] = "ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`;";
            }
        }

        // Add new FKs
        foreach ($newFKs as $newFK) {
            $fkName = $this->generateForeignKeyName($newFK);
            $found = false;
            foreach ($oldFKs as $oldFK) {
                if ($oldFK == $newFK) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $alterStatements[] = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$fkName}` FOREIGN KEY (`{$newFK['column']}`)
                REFERENCES `{$newFK['refTable']}`(`{$newFK['refColumn']}`)
                ON DELETE {$newFK['onDelete']}
                ON UPDATE {$newFK['onUpdate']};";
            }
        }

        return $alterStatements;
    }

    /**
     * Generate ALTER statements for dropping columns, indexes, and foreign keys
     * that exist in the current modalDefinition but are missing in modalDefinitionUpdate.
     *
     */
    public function generateDropMigration(): string
    {
        $table = $this->modalDefinition['modal_table'];
        return "DROP TABLE IF EXISTS `{$table}`;";
    }
    
    // ----------------------------
    // Helper to get table name
    // ----------------------------

    /**
     * Retrieves the name of the table from the modal definition.
     *
     * @return string The name of the table defined in the modal definition.
     */
    public function getTable(): string
    {
        return $this->modalDefinition['modal_table'];
    }

    /**
     * Loads and attaches related records to the given array of primary records based on defined relationships.
     *
     * @param array $records An array of primary records to which related records will be loaded and attached. Defaults to an empty array.
     * @return array The updated array of primary records with related records attached.
     * @throws \ReflectionException
     */
    public function loadRelations(array $records = []): array
    {
        foreach ($this->modalDefinition['modal_foreign_keys'] as $fk) {
            $relatedClass = $fk['model']; // Modal class
            $foreignKey = $fk['column'];  // column in this table
            $localKey = $fk['refColumn'] ?? 'id';

            $relatedModal = $relatedClass::getModal(self::$connection);

            // Collect foreign key values
            $fkValues = array_map(fn($r) => $r[$foreignKey], $records);
            $fkValues = array_unique($fkValues);

            if (empty($fkValues)) continue;

            // Fetch related records
            $relatedRecords = $relatedModal->whereIn($localKey, $fkValues)->get();

            // Map related records by localKey
            $relatedMap = [];
            foreach ($relatedRecords as $r) {
                $relatedMap[$r[$localKey]] = $r;
            }

            // Attach a related record to each main record
            foreach ($records as &$record) {
                $relationName = strtolower((new \ReflectionClass($relatedClass))->getShortName());
                $record[$relationName] = $relatedMap[$record[$foreignKey]] ?? null;
            }
        }

        return $records;
    }


}
