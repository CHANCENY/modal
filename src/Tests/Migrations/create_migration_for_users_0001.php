<?php

namespace Simp\Modal\Tests\Migrations;

class CreateMigrationForUsers001 {

	public function up(): string
	{
		return 'CREATE TABLE `users` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(100) NOT NULL,
			`email` VARCHAR(150) NOT NULL UNIQUE,
			`password` VARCHAR(255) NOT NULL,
			`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`deleted_at` DATETIME,
			PRIMARY KEY (`id`)
		) AUTO_INCREMENT=1;';
	}

	public function modify(): array
	{
		return array (
			0 => 'ALTER TABLE `users` MODIFY COLUMN `name` VARCHAR(200) NOT NULL;',
			1 => 'ALTER TABLE `users` ADD COLUMN `status` ENUM("ACTIVE", "INACTIVE") NOT NULL DEFAULT \'active\';',
		);
	}

	public function getTable(): string
	{
		return 'users';
	}

	public function down(): string
	{
		return 'DROP TABLE IF EXISTS `users`;';
	}
}
return CreateMigrationForUsers001::class;
