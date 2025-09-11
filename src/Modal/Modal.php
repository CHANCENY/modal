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

    public function __construct(?PDO $pdo = null)
    {
        self::$connection = $pdo;
    }

    public static function getModal(PDO $pdo): static
    {
        return new static($pdo);
    }

    /* -------------------- Mass Assignment -------------------- */
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

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function clearAttributes(): void
    {
        $this->attributes = [];
    }

    /* -------------------- Query Builder -------------------- */

    public function where(string $column, string $operator, mixed $value): self
    {
        $this->queryConditions[] = [$column, $operator, $value];
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->queryConditions[] = [$column, 'IN', $values];
        return $this;
    }

    public function whereNotIn(string $column, array $values): self
    {
        $this->queryConditions[] = [$column, 'NOT IN', $values];
        return $this;
    }

    public function orWhere(string $column, string $operator, mixed $value): self
    {
        $this->queryConditions[] = ['OR', $column, $operator, $value];
        return $this;
    }

    public function orWhereIn(string $column, array $values): self
    {
        $this->queryConditions[] = ['OR IN', $column, null, $values];
        return $this;
    }

    public function orWhereNotIn(string $column, array $values): self
    {
        $this->queryConditions[] = ['OR NOT IN', $column, null, $values];
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy = "`$column` $direction";
        return $this;
    }

    public function with(array|string $relations): self
    {
        if (is_string($relations)) $relations = [$relations];
        $this->withRelations = $relations;
        return $this;
    }

    /* -------------------- Column Selection -------------------- */
    public function select(array|string $columns): self
    {
        if (is_string($columns)) $columns = [$columns];
        $this->selects = $columns;
        return $this;
    }

    protected function buildSelect(): string
    {
        if (!empty($this->selects)) {
            $cols = array_map(fn($c) => "`$c`", $this->selects);
            return implode(',', $cols);
        }
        return '*';
    }

    /* -------------------- WHERE and Modifiers -------------------- */
    protected function buildWhere(array &$params): string
    {
        if (empty($this->queryConditions)) return '';

        $parts = [];

        foreach ($this->queryConditions as $i => $cond) {
            if (count($cond) === 3) {
                [$col, $op, $val] = $cond;
                $prefix = 'AND';
            } else {
                [$type, $col, $op, $val] = $cond;
                $prefix = ($type === 'OR' || str_starts_with($type, 'OR')) ? 'OR' : 'AND';
                if (in_array($type, ['OR IN', 'OR NOT IN'])) {
                    $op = strtoupper(str_replace('OR ', '', $type));
                }
            }

            if (in_array(strtoupper($op), ['IN', 'NOT IN'])) {
                if (empty($val)) continue;
                $placeholders = [];
                foreach ($val as $j => $v) {
                    $key = "w_" . preg_replace('/\W+/', '_', $col) . "_{$i}_{$j}";
                    $placeholders[] = ":$key";
                    $params[$key] = $v;
                }
                $parts[] = "$prefix `$col` $op (" . implode(',', $placeholders) . ")";
            } else {
                $key = "w_" . preg_replace('/\W+/', '_', $col) . "_$i";
                $parts[] = "$prefix `$col` $op :$key";
                $params[$key] = $val;
            }
        }

        if (empty($parts)) return '';

        $sql = implode(' ', $parts);
        $sql = preg_replace('/^(AND|OR)\s+/', '', $sql);
        return " WHERE $sql";
    }

    protected function buildModifiers(): string
    {
        $sql = '';
        if ($this->orderBy) $sql .= " ORDER BY {$this->orderBy}";
        if ($this->limit !== null) $sql .= " LIMIT {$this->limit}";
        if ($this->offset !== null) $sql .= " OFFSET {$this->offset}";
        return $sql;
    }

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

    protected function getConnection(): PDO
    {
        if (!self::$connection) throw new \RuntimeException("Database connection not set.");
        return self::$connection;
    }

    /* -------------------- CRUD -------------------- */
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
    public function hasOne(string $relatedClass, string $foreignKey, string $localKey = 'id'): ?array
    {
        $related = new $relatedClass(self::$connection);
        if (!isset($this->$localKey)) return null;
        return $related->where($foreignKey, '=', $this->$localKey)->first();
    }

    public function hasMany(string $relatedClass, string $foreignKey, string $localKey = 'id'): array
    {
        $related = new $relatedClass(self::$connection);
        if (!isset($this->$localKey)) return [];
        return $related->where($foreignKey, '=', $this->$localKey)->get();
    }

    public function belongsTo(string $refModalClass, string $refColumn, mixed $value): ?array
    {
        $refModal = $refModalClass::getModal(self::$connection);
        if ($value === null) return null;
        return $refModal->where($refColumn, '=', $value)->first();
    }

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

    public function defineRelation(string $name, callable $callback): void
    {
        $this->relations[$name] = $callback;
    }
}
