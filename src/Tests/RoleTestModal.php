<?php

namespace Simp\Modal\Tests;

use Simp\Modal\Modal\Modal;

class RoleTestModal extends Modal
{
    public function __construct(?\PDO $pdo = null)
    {
        parent::__construct($pdo);

        // Table name
        $this->setTable('roles');

        // Columns
        $this->addColumn('id', 'INT', ['auto_increment' => true]);
        $this->addColumn('role_name', 'VARCHAR(100)', ['nullable' => false, 'unique' => true]);
        $this->addColumn('uid', 'INT', ['nullable' => false]); // foreign key → users.id
        $this->addColumn('description', 'TEXT', ['nullable' => true]);

        // Primary key
        $this->setPrimaryKey('id');

        // Enable timestamps + soft deletes
        $this->modalDefinition['modal_timestamps'] = true;
        $this->modalDefinition['modal_soft_deletes'] = true;

        // Foreign key
        $this->addForeignKey('uid', UserTestModal::class, 'id');

        // Relation: Role belongsTo User
        $this->belongsToRelation(UserTestModal::class, 'uid', 'id', 'user');
    }
}
