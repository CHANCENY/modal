<?php

use Simp\Modal\Tests\RoleTestModal;
use Simp\Modal\Tests\UserTestModal;

require_once 'vendor/autoload.php';

$db = new PDO('mysql:host=database;dbname=lamp', 'lamp', 'lamp');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

// -------------------- INITIALIZE MODALS --------------------
$userModal = UserTestModal::getModal($db);
$roleModal = RoleTestModal::getModal($db);

// -------------------- CLEAN TABLES --------------------
$db->exec("DELETE FROM {$roleModal->getTable()}");
$db->exec("ALTER TABLE {$roleModal->getTable()} AUTO_INCREMENT = 1");
$db->exec("DELETE FROM {$userModal->getTable()}");
$db->exec("ALTER TABLE {$userModal->getTable()} AUTO_INCREMENT = 1");

dump("==== TABLES CLEARED ====");

// -------------------- INSERT USERS --------------------
$aliceId   = $userModal->fill(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => 'secret'])->insert();
$bobId     = $userModal->fill(['name' => 'Bob', 'email' => 'bob@example.com', 'password' => 'pass'])->insert();
$charlieId = $userModal->fill(['name' => 'Charlie', 'email' => 'charlie@example.com', 'password' => '1234'])->insert();

dump("==== INSERTED USERS ====");
//dump($userModal->get());


// -------------------- INSERT ROLES --------------------
$roleModal->fill(['role_name' => 'Admin', 'uid' => $aliceId])->insert();
$roleModal->fill(['role_name' => 'Editor', 'uid' => $bobId])->insert();
$roleModal->fill(['role_name' => 'Subscriber', 'uid' => $bobId])->insert();
$roleModal->fill(['role_name' => 'Viewer', 'uid' => $charlieId])->insert();

dump("==== INSERTED ROLES ====");
dump($roleModal->get());

// -------------------- SELECT SPECIFIC COLUMNS --------------------
$usersSelected = $userModal
    ->select(['id', 'name'])
    ->limit(3)
    ->orderBy('id', 'ASC')
    ->get();

dump("==== SELECTED COLUMNS ====");
dump($usersSelected);


// -------------------- HAS ONE RELATION (User -> Role) --------------------
$alice = $userModal->where('id', '=', $aliceId)->first();
$aliceRole = $roleModal->where('uid', '=', $alice['id'])->select(['role_name', 'uid'])->first();

dump("==== HAS ONE RELATION (Alice -> Role) ====");
dump(['user' => $alice, 'role' => $aliceRole]);


// -------------------- HAS MANY RELATION (User -> Roles) --------------------
$bob = $userModal->where('id', '=', $bobId)->first();
$bobRoles = $roleModal->where('uid', '=', $bob['id'])->select(['role_name','uid'])->get();

dump("==== HAS MANY RELATION (Bob -> Roles) ====");
dump(['user' => $bob, 'roles' => $bobRoles]);

// -------------------- BELONGS TO RELATION (Role -> User) --------------------
$editorRole = $roleModal->where('role_name', '=', 'Editor')->first();
$editorUser = $userModal->where('id', '=', $editorRole['uid'])->select(['name', 'email'])->first();

dump("==== BELONGS TO RELATION (Editor Role -> User) ====");
dump(['role' => $editorRole, 'user' => $editorUser]);

// -------------------- COMPLEX QUERY WITH WHERE IN AND OR WHERE --------------------
$complexUsers = $userModal
    ->whereIn('id', [$aliceId, $bobId])
    ->orWhere('name', '=', 'Charlie')
    ->select(['id', 'name', 'email'])
    ->orderBy('id', 'DESC')
    ->get();

// Attach hasMany roles for each
foreach ($complexUsers as &$user) {
    $user['roles'] = $roleModal->where('uid', '=', $user['id'])->select(['role_name','uid'])->get();
}

dump("==== COMPLEX QUERY WITH ROLES ====");
dump($complexUsers);

// -------------------- UPDATE EXAMPLE --------------------
$updateCount = $userModal->where('id', '=', $aliceId)
    ->fill(['name' => 'Alice Updated'])
    ->update();

dump("==== UPDATED USERS ====");
dump($userModal->where('id', '=', $aliceId)->first());

// -------------------- DELETE EXAMPLE --------------------
$deleteCount = $roleModal->where('role_name', '=', 'Viewer')->delete();

dump("==== DELETED ROLES ====");
dump($roleModal->get());

// -------------------- MEGA DEMO COMPLETE --------------------
dump("==== MEGA DEMO COMPLETE ====");
