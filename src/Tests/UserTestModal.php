<?php

namespace Simp\Modal\Tests;

use Simp\Modal\Modal\Modal;

class UserTestModal extends Modal
{
    public function __construct(?\PDO $pdo = null)
    {
        parent::__construct($pdo);
        // Set the table name
        $this->setTable('users');

        // Add columns
        $this->addColumn('id', 'INT', ['auto_increment' => true]);
        $this->addColumn('name', 'VARCHAR(100)', ['nullable' => false]);
        $this->addColumn('email', 'VARCHAR(150)', ['nullable' => false, 'unique' => true]);
        $this->addColumn('password', 'VARCHAR(255)', ['nullable' => false]);

        // Set a primary key
        $this->setPrimaryKey('id');

        // Enable timestamps and soft deletes
        $this->modalDefinition['modal_timestamps'] = true;
        $this->modalDefinition['modal_soft_deletes'] = true;

        $this->addColumnUpdate('name', 'VARCHAR(200)', ['nullable' => false]);
        $this->addColumnUpdate('status', 'ENUM("active", "inactive")', ['default' => 'active']);

        // Define hasMany relation
        $this->defineRelation('roles', function (array $userIds) {
            $roleModal = new RoleTestModal(self::$connection);
            $roles = $roleModal->whereIn('uid', $userIds)->get();

            $grouped = [];
            foreach ($roles as $role) {
                $grouped[$role['uid']][] = $role;
            }
            return $grouped;
        });

    }
}