<?php

use PHPUnit\Framework\TestCase;
use Simp\Modal\Tests\RoleTestModal;
use Simp\Modal\Tests\UserTestModal;

class ModalRelationsTest extends TestCase
{
    private PDO $pdo;
    private UserTestModal $userModal;
    private RoleTestModal $roleModal;

    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=database;dbname=lamp', 'lamp', 'lamp');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        $this->userModal = UserTestModal::getModal($this->pdo);
        $this->roleModal = RoleTestModal::getModal($this->pdo);

        // Clean up tables
        $this->pdo->exec("DELETE FROM {$this->roleModal->getTable()}");
        $this->pdo->exec("ALTER TABLE {$this->roleModal->getTable()} AUTO_INCREMENT = 1");
        $this->pdo->exec("DELETE FROM {$this->userModal->getTable()}");
        $this->pdo->exec("ALTER TABLE {$this->userModal->getTable()} AUTO_INCREMENT = 1");
    }

    public function testHasOneRelation(): void
    {
        $userId = $this->userModal->fill([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret'
        ])->insert();

        $this->roleModal->fill([
            'role_name' => 'Admin',
            'uid' => $userId,
            'description' => 'Administrator role'
        ])->insert();

        $user = $this->userModal->where('id', '=', $userId)->first();
        $role = $this->roleModal->where('uid', '=', $user['id'])->select(['role_name', 'uid'])->first();

        $this->assertEquals('Admin', $role['role_name']);
        $this->assertArrayNotHasKey('description', $role);
    }

    public function testBelongsToRelation(): void
    {
        $userId = $this->userModal->fill([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'pass'
        ])->insert();

        $roleId = $this->roleModal->fill([
            'role_name' => 'Editor',
            'uid' => $userId,
            'description' => 'Editor role'
        ])->insert();

        $role = $this->roleModal->where('id', '=', $roleId)->select(['role_name', 'uid'])->first();
        $user = $this->userModal->where('id', '=', $role['uid'])->select(['name', 'id'])->first();

        $this->assertEquals('Bob', $user['name']);
        $this->assertArrayNotHasKey('email', $user);
    }

    public function testHasManyRelation(): void
    {
        $userId = $this->userModal->fill([
            'name' => 'Charlie',
            'email' => 'charlie@example.com',
            'password' => '1234'
        ])->insert();

        $this->roleModal->fill(['role_name' => 'Subscriber1', 'uid' => $userId])->insert();
        $this->roleModal->fill(['role_name' => 'Subscriber2', 'uid' => $userId])->insert();

        $roles = $this->roleModal->where('uid', '=', $userId)->select(['role_name'])->get();

        $this->assertCount(2, $roles);
        $this->assertEquals('Subscriber1', $roles[0]['role_name']);
        $this->assertEquals('Subscriber2', $roles[1]['role_name']);
        $this->assertArrayNotHasKey('uid', $roles[0]);
        $this->assertArrayNotHasKey('uid', $roles[1]);
    }

    public function testWhereInWithSelect(): void
    {
        $userId1 = $this->userModal->fill(['name' => 'U1', 'email' => 'u1@example.com', 'password' => 'a'])->insert();
        $userId2 = $this->userModal->fill(['name' => 'U2', 'email' => 'u2@example.com', 'password' => 'b'])->insert();

        $this->roleModal->fill(['role_name' => 'R1', 'uid' => $userId1])->insert();
        $this->roleModal->fill(['role_name' => 'R2', 'uid' => $userId2])->insert();
        $this->roleModal->fill(['role_name' => 'R3', 'uid' => $userId2])->insert();

        $roles = $this->roleModal->whereIn('uid', [$userId1, $userId2])->select(['role_name'])->get();

        $this->assertCount(3, $roles);
        $this->assertEquals(['R1','R2','R3'], array_column($roles,'role_name'));
        $this->assertArrayNotHasKey('uid', $roles[0]);
    }

    public function testOrWhereWithSelect(): void
    {
        $userId1 = $this->userModal->fill(['name' => 'X1', 'email' => 'x1@example.com', 'password' => 'a'])->insert();
        $userId2 = $this->userModal->fill(['name' => 'X2', 'email' => 'x2@example.com', 'password' => 'b'])->insert();

        $this->roleModal->fill(['role_name' => 'RoleX1', 'uid' => $userId1])->insert();
        $this->roleModal->fill(['role_name' => 'RoleX2', 'uid' => $userId2])->insert();

        $roles = $this->roleModal
            ->where('uid', '=', $userId1)
            ->orWhere('uid', '=', $userId2)
            ->select(['role_name'])
            ->get();

        $this->assertCount(2, $roles);
        $this->assertEquals(['RoleX1','RoleX2'], array_column($roles,'role_name'));
        $this->assertArrayNotHasKey('uid', $roles[0]);
    }
}
