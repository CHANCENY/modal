<?php

namespace Simp\Modal\Tests\Migrations;

class CreateMigrationForRoles003 {

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
			PRIMARY KEY (`id`),
			CONSTRAINT `fk_roles_uid` FOREIGN KEY (`uid`)
																 REFERENCES `users`(`id`)
																 ON DELETE CASCADE
																 ON UPDATE CASCADE
		) AUTO_INCREMENT=1;';
	}

	public function modify(): array
	{
		return array (
			0 => 'ALTER TABLE `roles` DROP FOREIGN KEY `fk_roles_uid`;',
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
return CreateMigrationForRoles003::class;
