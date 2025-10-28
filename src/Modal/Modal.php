<?php

namespace Simp\Modal\Modal;

use PDO;

abstract class Modal
{
    use ModalHelper;

    protected array $queryConditions = [];
    protected ?string $orderBy = null;
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $joins = [];
    protected array $selects = []; // Added for column selection
    protected array $withRelations = [];
    protected array $attributes = [];
    protected array $relations = [];

    /**
     * @param PDO|null $pdo
     */
    public function __construct(?PDO $pdo = null)
    {
        self::$connection = $pdo;
    }

    /**
     * Get the modal instance.
     * @param PDO $pdo
     * @return static
     */
    public static function getModal(PDO $pdo): static
    {
        return new static($pdo);
    }

    /* -------------------- Mass Assignment -------------------- */

    /**
     * Fill the model with data.
     * @param array $data
     * @return $this
     */
    public function fill(array $data): self
    {
        if (!empty($this->modalDefinition['modal_fillable'])) {
            foreach ($data as $key => $value) {
                if (in_array($key, $this->modalDefinition['modal_fillable'])) {
                    $this->attributes[$key] = $value;
                }
            }
        } else {
            foreach ($data as $key => $value) {
                if (empty($this->modalDefinition['modal_guarded']) || !in_array($key, $this->modalDefinition['modal_guarded'])) {
                    $this->attributes[$key] = $value;
                }
            }
        }
        return $this;
    }

    /**
     * Get all attributes.
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Clear all attributes.
     * @return void
     */
    public function clearAttributes(): void
    {
        $this->attributes = [];
    }

    /* -------------------- Query Builder -------------------- */

    /**
     * Add a "WHERE" clause to the query.
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->queryConditions[] = [$column, $operator, $value];
        return $this;
    }

    /**
     * Add an "IN" condition to the query.
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereIn(string $column, array $values): self
    {
        $this->queryConditions[] = [$column, 'IN', $values];
        return $this;
    }

    /**
     * Add an "IN" condition to the query.
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereNotIn(string $column, array $values): self
    {
        $this->queryConditions[] = [$column, 'NOT IN', $values];
        return $this;
    }

    /**
     * Add a "JOIN" clause to the query.
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function orWhere(string $column, string $operator, mixed $value): self
    {
        $this->queryConditions[] = ['OR', $column, $operator, $value];
        return $this;
    }

    /**
     * Add an "OR IN" condition to the query.
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function orWhereIn(string $column, array $values): self
    {
        $this->queryConditions[] = ['OR IN', $column, null, $values];
        return $this;
    }

    /**
     * Add an "OR NOT IN" condition to the query.
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function orWhereNotIn(string $column, array $values): self
    {
        $this->queryConditions[] = ['OR NOT IN', $column, null, $values];
        return $this;
    }

    /**
     * Set the limit for the query.
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the offset for the query.
     * @param int $offset
     * @return $this
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Set the order by clause for the query.
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy = "`$column` $direction";
        return $this;
    }

    /**
     * Add relations to the query.
     * @param array|string $relations
     * @return $this
     */
    public function with(array|string $relations): self
    {
        if (is_string($relations)) $relations = [$relations];
        $this->withRelations = $relations;
        return $this;
    }

    /* -------------------- Column Selection -------------------- */

    /**
     * Set the columns to select for the query.
     * @param array|string $columns
     * @return $this
     */
    public function select(array|string $columns): self
    {
        if (is_string($columns)) $columns = [$columns];
        $this->selects = $columns;
        return $this;
    }

    /**
     * Build the SELECT clause for the query.
     * @return string
     */
    protected function buildSelect(): string
    {
        if (!empty($this->selects)) {
            $cols = array_map(fn($c) => "`$c`", $this->selects);
            return implode(',', $cols);
        }
        return '*';
    }

    /* -------------------- WHERE and Modifiers -------------------- */

    /**
     * Build the WHERE clause for the query.
     * @param array $params
     * @return string
     */
    protected function buildWhere(array &$params): string
    {
        if (empty($this->queryConditions)) return '';

        $parts = [];

        foreach ($this->queryConditions as $i => $cond) {
            // Detect pattern type
            if (count($cond) === 3) {
                [$col, $op, $val] = $cond;
                $prefix = 'AND';
                $type = '';
            } else {
                [$type, $col, $op, $val] = $cond;
                $prefix = ($type === 'OR' || str_starts_with($type, 'OR')) ? 'OR' : 'AND';
            }

            // --- Handle RAW conditions (whereRaw) ---
            if ($type === 'RAW') {
                $rawSql = $col;
                $bindings = $op ?? [];
                foreach ($bindings as $k => $v) {
                    $paramKey = "w_raw_{$i}_{$k}";
                    $params[$paramKey] = $v;
                    $rawSql = str_replace('?', ":$paramKey", $rawSql);
                }
                $parts[] = "$prefix ($rawSql)";
                continue;
            }

            // --- Handle BETWEEN ---
            if (strtoupper($op) === 'BETWEEN' || strtoupper($type) === 'BETWEEN') {
                [$start, $end] = $val;
                $k1 = "w_" . preg_replace('/\W+/', '_', $col) . "_{$i}_start";
                $k2 = "w_" . preg_replace('/\W+/', '_', $col) . "_{$i}_end";
                $params[$k1] = $start;
                $params[$k2] = $end;
                $parts[] = "$prefix `$col` BETWEEN :$k1 AND :$k2";
                continue;
            }

            // --- Handle OR BETWEEN ---
            if (strtoupper($type) === 'OR BETWEEN') {
                [$start, $end] = $val;
                $k1 = "w_" . preg_replace('/\W+/', '_', $col) . "_{$i}_start";
                $k2 = "w_" . preg_replace('/\W+/', '_', $col) . "_{$i}_end";
                $params[$k1] = $start;
                $params[$k2] = $end;
                $parts[] = "OR `$col` BETWEEN :$k1 AND :$k2";
                continue;
            }

            // --- Handle SEARCH (multi-column LIKE) ---
            if (strtoupper($type) === 'SEARCH') {
                $columns = (array) $col;
                $keyword = $val;
                $searchParts = [];
                foreach ($columns as $j => $c) {
                    $k = "w_search_" . preg_replace('/\W+/', '_', $c) . "_{$i}_{$j}";
                    $params[$k] = "%$keyword%";
                    $searchParts[] = "`$c` LIKE :$k";
                }
                $parts[] = "$prefix (" . implode(' OR ', $searchParts) . ")";
                continue;
            }

            // --- Handle IN and NOT IN ---
            if (in_array(strtoupper($op), ['IN', 'NOT IN'])) {
                if (empty($val)) continue;
                $placeholders = [];
                foreach ($val as $j => $v) {
                    $key = "w_" . preg_replace('/\W+/', '_', $col) . "_{$i}_{$j}";
                    $placeholders[] = ":$key";
                    $params[$key] = $v;
                }
                $parts[] = "$prefix `$col` $op (" . implode(',', $placeholders) . ")";
                continue;
            }

            // --- Default: simple condition ---
            $key = "w_" . preg_replace('/\W+/', '_', $col) . "_$i";
            $parts[] = "$prefix `$col` $op :$key";
            $params[$key] = $val;
        }

        if (empty($parts)) return '';

        $sql = implode(' ', $parts);
        $sql = preg_replace('/^(AND|OR)\s+/', '', $sql);
        return " WHERE $sql";
    }

    /**
     * Build the ORDER BY and LIMIT clauses for the query.
     * @return string
     */
    protected function buildModifiers(): string
    {
        $sql = '';
        if ($this->orderBy) $sql .= " ORDER BY {$this->orderBy}";
        if ($this->limit !== null) $sql .= " LIMIT {$this->limit}";
        if ($this->offset !== null) $sql .= " OFFSET {$this->offset}";
        return $sql;
    }

    /**
     * Reset the query builder state.
     * @return void
     */
    protected function resetQuery(): void
    {
        $this->queryConditions = [];
        $this->orderBy = null;
        $this->limit = null;
        $this->offset = null;
        $this->joins = [];
        $this->selects = [];
        $this->withRelations = [];
        $this->clearAttributes();
    }

    /**
     * Get the database connection.
     * @return PDO
     */
    protected function getConnection(): PDO
    {
        if (!self::$connection) throw new \RuntimeException("Database connection not set.");
        return self::$connection;
    }

    /* -------------------- CRUD -------------------- */

    /**
     * Get the first record from the database.
     * @return array|null
     * @throws \ReflectionException
     */
    public function first(): ?array
    {
        $params = [];
        $sql = "SELECT {$this->buildSelect()} FROM `{$this->modalDefinition['modal_table']}`" .
            $this->buildWhere($params) .
            $this->buildModifiers() . " LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $this->resetQuery();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->loadRelations([$result])[0] : null;
    }

    /**
     * Get all records from the database.
     * @return array
     * @throws \ReflectionException
     */
    public function get(): array
    {
        $params = [];
        $sql = "SELECT {$this->buildSelect()} FROM `{$this->modalDefinition['modal_table']}`" .
            $this->buildWhere($params) .
            $this->buildModifiers();
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $this->resetQuery();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->loadRelations($results);
    }

    /**
     * Insert a new record into the database.
     * @param array $data
     * @return int
     */
    public function insert(array $data = []): int
    {
        $data = $data ?: $this->attributes;
        if (empty($data)) throw new \RuntimeException("No data provided for insert.");
        $table = $this->modalDefinition['modal_table'];
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $cols);
        $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($data as $k => $v) $stmt->bindValue(":$k", $v);
        $stmt->execute();
        $this->resetQuery();
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Update a record in the database.
     * @param array $data
     * @return int
     */
    public function update(array $data = []): int
    {
        $data = $data ?: $this->attributes;
        if (empty($data)) throw new \RuntimeException("No data provided for update.");
        if (empty($this->queryConditions)) throw new \RuntimeException("Update requires at least one WHERE condition.");
        $table = $this->modalDefinition['modal_table'];
        $params = [];
        $set = [];
        foreach ($data as $k => $v) {
            $set[] = "`$k` = :set_$k";
            $params["set_$k"] = $v;
        }
        $sql = "UPDATE `$table` SET " . implode(',', $set) . $this->buildWhere($params);
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $affected = $stmt->rowCount();
        $this->resetQuery();
        return $affected;
    }

    /**
     * Delete a record from the database.
     * @return int
     */
    public function delete(): int
    {
        if (empty($this->queryConditions)) throw new \RuntimeException("Delete requires at least one WHERE condition.");
        $params = [];
        $sql = "DELETE FROM `{$this->modalDefinition['modal_table']}`" . $this->buildWhere($params);
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $affected = $stmt->rowCount();
        $this->resetQuery();
        return $affected;
    }

    /* -------------------- Relations -------------------- */

    /**
     * Get the related record for a has-one relationship.
     * @param string $relatedClass
     * @param string $foreignKey
     * @param string $localKey
     * @return array|null
     */
    public function hasOne(string $relatedClass, string $foreignKey, string $localKey = 'id'): ?array
    {
        $related = new $relatedClass(self::$connection);
        if (!isset($this->$localKey)) return null;
        return $related->where($foreignKey, '=', $this->$localKey)->first();
    }

    /**
     * Get the related records for a has-many relationship.
     * @param string $relatedClass
     * @param string $foreignKey
     * @param string $localKey
     * @return array
     */
    public function hasMany(string $relatedClass, string $foreignKey, string $localKey = 'id'): array
    {
        $related = new $relatedClass(self::$connection);
        if (!isset($this->$localKey)) return [];
        return $related->where($foreignKey, '=', $this->$localKey)->get();
    }

    /**
     * Get the related record for a belongs-to relationship.
     * @param string $refModalClass
     * @param string $refColumn
     * @param mixed $value
     * @return array|null
     */
    public function belongsTo(string $refModalClass, string $refColumn, mixed $value): ?array
    {
        $refModal = $refModalClass::getModal(self::$connection);
        if ($value === null) return null;
        return $refModal->where($refColumn, '=', $value)->first();
    }

    /**
     * Load relations for a set of records.
     * @param array $results
     * @param string $relation
     * @param callable $callback
     * @return array
     */
    protected function loadEagerRelation(array $results, string $relation, callable $callback): array
    {
        $localKeys = [];
        foreach ($results as $row) {
            foreach ($this->getPrimaryKeys() as $pk) {
                if (isset($row[$pk])) $localKeys[] = $row[$pk];
            }
        }
        $localKeys = array_unique($localKeys);
        $relatedData = $callback($localKeys);
        foreach ($results as &$row) {
            $row[$relation] = $relatedData[$row['id']] ?? ($callback instanceof \Closure ? [] : null);
        }
        return $results;
    }

    /**
     * Load relations for a set of records.
     * @param string $name
     * @param callable $callback
     * @return void
     */
    public function defineRelation(string $name, callable $callback): void
    {
        $this->relations[$name] = $callback;
    }

    /**
     * Build a relation function for a has-many relationship.
     * @param string $relatedClass
     * @param string $foreignKey
     * @param string $localKey
     * @param string $relationName
     * @return void
     */
    public function hasManyRelation(string $relatedClass, string $foreignKey, string $localKey, string $relationName): void
    {
        $this->defineRelation($relationName, function (array $ids) use ($relatedClass, $foreignKey, $localKey) {
            $related = new $relatedClass(self::$connection);
            $records = $related->whereIn($foreignKey, $ids)->get();

            $grouped = [];
            foreach ($records as $record) {
                $grouped[$record[$foreignKey]][] = $record;
            }
            return $grouped;
        });
    }

    /**
     * Build a relation function for a belongs-to relationship.
     * @param string $relatedClass
     * @param string $foreignKey
     * @param string $ownerKey
     * @param string $relationName
     * @return void
     */
    public function belongsToRelation(string $relatedClass, string $foreignKey, string $ownerKey, string $relationName): void
    {
        $this->defineRelation($relationName, function (array $ids) use ($relatedClass, $foreignKey, $ownerKey) {
            $related = new $relatedClass(self::$connection);
            $records = $related->whereIn($ownerKey, $ids)->get();

            $map = [];
            foreach ($records as $record) {
                $map[$record[$ownerKey]] = $record;
            }
            return $map;
        });
    }

    /**
     * Defines a "has-one" relationship between the current model and a related model.
     *
     * @param string $relatedClass The fully qualified class name of the related model.
     * @param string $foreignKey The foreign key column in the related model.
     * @param string $localKey The primary key column in the current model used to match the foreign key.
     * @param string $relationName The name of the relationship being defined.
     *
     * @return void
     */
    public function hasOneRelation(string $relatedClass, string $foreignKey, string $localKey, string $relationName): void
    {
        $this->defineRelation($relationName, function (array $ids) use ($relatedClass, $foreignKey, $localKey) {
            $related = new $relatedClass(self::$connection);
            $records = $related->whereIn($foreignKey, $ids)->get();

            $map = [];
            foreach ($records as $record) {
                $map[$record[$foreignKey]] = $record;
            }
            return $map;
        });
    }

    //--------------NEW methods---------------------//
    /**
     * Add a raw WHERE condition.
     * @param string $rawSql
     * @param array $bindings
     * @return $this
     */
    public function whereRaw(string $rawSql, array $bindings = []): self
    {
        $this->queryConditions[] = ['RAW', $rawSql, $bindings];
        return $this;
    }

    /**
     * Add a BETWEEN condition.
     * @param string $column
     * @param mixed $start
     * @param mixed $end
     * @return $this
     */
    public function whereBetween(string $column, mixed $start, mixed $end): self
    {
        $this->queryConditions[] = [$column, 'BETWEEN', [$start, $end]];
        return $this;
    }

    /**
     * Add an OR BETWEEN condition.
     * @param string $column
     * @param mixed $start
     * @param mixed $end
     * @return $this
     */
    public function orWhereBetween(string $column, mixed $start, mixed $end): self
    {
        $this->queryConditions[] = ['OR BETWEEN', $column, null, [$start, $end]];
        return $this;
    }

    /**
     * Count records matching the query.
     * @return int
     */
    public function count(): int
    {
        $params = [];
        $sql = "SELECT COUNT(*) as aggregate FROM `{$this->modalDefinition['modal_table']}`" .
            $this->buildWhere($params);
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $this->resetQuery();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Sum a column.
     * @param string $column
     * @return float|int
     */
    public function sum(string $column): float|int
    {
        $params = [];
        $sql = "SELECT SUM(`$column`) as aggregate FROM `{$this->modalDefinition['modal_table']}`" .
            $this->buildWhere($params);
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $this->resetQuery();
        return (float)$stmt->fetchColumn();
    }

    /**
     * Get the average value of a column.
     * @param string $column
     * @return float|int
     */
    public function avg(string $column): float|int
    {
        $params = [];
        $sql = "SELECT AVG(`$column`) as aggregate FROM `{$this->modalDefinition['modal_table']}`" .
            $this->buildWhere($params);
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $this->resetQuery();
        return (float)$stmt->fetchColumn();
    }

    /**
     * Find record by primary key.
     * @param mixed $id
     * @param string $primaryKey
     * @return array|null
     */
    public function find(mixed $id, string $primaryKey = 'id'): ?array
    {
        return $this->where($primaryKey, '=', $id)->first();
    }

    /**
     * Check if a record exists.
     * @return bool
     */
    public function exists(): bool
    {
        $params = [];
        $sql = "SELECT 1 FROM `{$this->modalDefinition['modal_table']}`" .
            $this->buildWhere($params) .
            " LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $this->resetQuery();
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Paginate results.
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function paginate(int $perPage = 10, int $page = 1): array
    {
        $offset = ($page - 1) * $perPage;
        $results = $this->limit($perPage)->offset($offset)->get();
        $total = $this->count();

        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
        ];
    }

    /**
     * Clone current query instance (without execution).
     * @return static
     */
    public function cloneQuery(): static
    {
        $clone = clone $this;
        $clone->attributes = $this->attributes;
        $clone->queryConditions = $this->queryConditions;
        $clone->orderBy = $this->orderBy;
        $clone->limit = $this->limit;
        $clone->offset = $this->offset;
        $clone->selects = $this->selects;
        return $clone;
    }

    /**
     * Get all table columns.
     * @return array
     */
    public function getColumns(): array
    {
        $stmt = $this->getConnection()->query("DESCRIBE `{$this->modalDefinition['modal_table']}`");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get the last executed SQL with bound parameters replaced.
     * (useful for debugging)
     * @param string $sql
     * @param array $params
     * @return string
     */
    protected function interpolateSql(string $sql, array $params): string
    {
        foreach ($params as $key => $value) {
            $escaped = is_numeric($value) ? $value : "'" . addslashes($value) . "'";
            $sql = str_replace(":$key", $escaped, $sql);
        }
        return $sql;
    }

    /**
     * Search for a keyword in one or more columns.
     *
     * @param string|array $columns
     * @param string $keyword
     * @return $this
     */
    public function search(string|array $columns, string $keyword): self
    {
        if (is_string($columns)) $columns = [$columns];
        $this->queryConditions[] = ['SEARCH', $columns, 'LIKE', $keyword];
        return $this;
    }

}
