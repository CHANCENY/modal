<?php

use PHPUnit\Framework\TestCase;
use Simp\Modal\Tests\UserTestModal;
use PDO;

class ModalTest extends TestCase
{
    private PDO $pdo;
    private UserTestModal $userModal;

    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=database;dbname=lamp', 'lamp', 'lamp');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        $this->userModal = UserTestModal::getModal($this->pdo);

        // Clean up table to avoid duplicate entries
        $this->pdo->exec("DELETE FROM {$this->userModal->getTable()}");
        $this->pdo->exec("ALTER TABLE {$this->userModal->getTable()} AUTO_INCREMENT = 1");
    }

    public function testInsert(): void
    {
        $email = 'alice' . uniqid() . '@example.com';
        $id = $this->userModal->fill([
            'name' => 'Alice',
            'email' => $email,
            'password' => 'secret',
        ])->insert();

        $this->assertEquals(1, $id);

        $user = $this->userModal->where('id', '=', $id)->first();
        $this->assertEquals('Alice', $user['name']);
        $this->assertEquals($email, $user['email']);
    }

    public function testUpdate(): void
    {
        $email = 'bob' . uniqid() . '@example.com';
        $id = $this->userModal->fill([
            'name' => 'Bob',
            'email' => $email,
            'password' => 'pass',
        ])->insert();

        $affected = $this->userModal->where('id', '=', $id)
            ->fill(['name' => 'Bobby'])
            ->update();

        $this->assertEquals(1, $affected);

        $user = $this->userModal->where('id', '=', $id)->first();
        $this->assertEquals('Bobby', $user['name']);
    }

    public function testDelete(): void
    {
        $email = 'charlie' . uniqid() . '@example.com';
        $id = $this->userModal->fill([
            'name' => 'Charlie',
            'email' => $email,
            'password' => '1234',
        ])->insert();

        $affected = $this->userModal->where('id', '=', $id)->delete();
        $this->assertEquals(1, $affected);

        $user = $this->userModal->where('id', '=', $id)->first();
        $this->assertNull($user);
    }

    public function testGetMultiple(): void
    {
        $this->userModal->fill([
            'name' => 'D1',
            'email' => 'd1' . uniqid() . '@example.com',
            'password' => 'a'
        ])->insert();

        $this->userModal->fill([
            'name' => 'D2',
            'email' => 'd2' . uniqid() . '@example.com',
            'password' => 'b'
        ])->insert();

        $users = $this->userModal->get();
        $this->assertCount(2, $users);
    }

    public function testMassAssignmentGuard(): void
    {
        $email = 'guard' . uniqid() . '@example.com';
        $this->userModal->fill([
            'id' => 999, // should be ignored
            'name' => 'GuardTest',
            'email' => $email,
            'password' => '123'
        ])->insert();

        $user = $this->userModal->where('name', '=', 'GuardTest')->first();
        $this->assertNotEquals(999, $user['id']); // ID should auto-increment
    }

    /* -------------------- New Test for Select Columns -------------------- */
    public function testSelectColumns(): void
    {
        $email = 'select' . uniqid() . '@example.com';
        $id = $this->userModal->fill([
            'name' => 'SelectUser',
            'email' => $email,
            'password' => 'mypassword',
        ])->insert();

        // Only select name and email
        $user = $this->userModal
            ->where('id', '=', $id)
            ->select(['name', 'email'])
            ->first();

        $this->assertEquals('SelectUser', $user['name']);
        $this->assertEquals($email, $user['email']);
        $this->assertArrayNotHasKey('password', $user); // password should not be selected
    }
}
