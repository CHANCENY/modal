<?php

namespace Simp\Modal\Tests\Migrations;

class CreateMigrationForRoles002 {

	public function up(): string
	{
		return 'CREATE TABLE `roles` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`role_name` VARCHAR(100) NOT NULL UNIQUE,
			`uid` INT NOT NULL,
			`description` TEXT,
			`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`deleted_at` DATETIME,
			PRIMARY KEY (`id`)
		) AUTO_INCREMENT=1;';
	}

	public function modify(): array
	{
		return array (
		);
	}

	public function getTable(): string
	{
		return 'roles';
	}

	public function down(): string
	{
		return 'DROP TABLE IF EXISTS `roles`;';
	}
}
return CreateMigrationForRoles002::class;
