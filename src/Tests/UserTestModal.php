<?php

namespace Simp\Modal\Tests;

use Simp\Modal\Modal\Modal;

class UserTestModal extends Modal
{
    public function __construct(?\PDO $pdo = null)
    {
        parent::__construct($pdo);

        // Table name
        $this->setTable('users');

        // Columns
        $this->addColumn('id', 'INT', ['auto_increment' => true]);
        $this->addColumn('name', 'VARCHAR(100)', ['nullable' => false]);
        $this->addColumn('email', 'VARCHAR(150)', ['nullable' => false, 'unique' => true]);
        $this->addColumn('password', 'VARCHAR(255)', ['nullable' => false]);

        // Primary key
        $this->setPrimaryKey('id');

        // Enable timestamps + soft deletes
        $this->modalDefinition['modal_timestamps'] = true;
        $this->modalDefinition['modal_soft_deletes'] = true;

        // Example schema evolution
        $this->addColumnUpdate('name', 'VARCHAR(200)', ['nullable' => false]);
        $this->addColumnUpdate('status', 'ENUM("active", "inactive")', ['default' => 'active']);

        // Relation: User hasMany Roles
        $this->hasManyRelation(RoleTestModal::class, 'uid', 'id', 'roles');
    }
}
