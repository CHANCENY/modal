<?php

namespace Simp\Modal\Tests;

use Simp\Modal\Modal\Modal;

class RoleTestModal extends Modal
{
    public function __construct(?\PDO $pdo = null)
    {
        parent::__construct($pdo);
        // Set table name
        $this->setTable('roles');

        // Columns
        $this->addColumn('id', 'INT', ['auto_increment' => true]);
        $this->addColumn('role_name', 'VARCHAR(100)', ['nullable' => false, 'unique' => true]);
        $this->addColumn('uid', 'INT', ['nullable' => false]); // foreign key to UserModal
        $this->addColumn('description', 'TEXT', ['nullable' => true]);

        // Primary key
        $this->setPrimaryKey('id');

        // Enable timestamps and soft deletes
        $this->modalDefinition['modal_timestamps'] = true;
        $this->modalDefinition['modal_soft_deletes'] = true;

        // Add a foreign key to UserModal
        $this->addForeignKey('uid', UserTestModal::class, 'id');

        // Define belongsTo relation
        $this->defineRelation('user', function (array $roleUserIds) {
            $userModal = new UserTestModal(self::$connection);
            $users = $userModal->whereIn('id', $roleUserIds)->get();

            $map = [];
            foreach ($users as $user) {
                $map[$user['id']] = $user;
            }
            return $map;
        });
    }
}
