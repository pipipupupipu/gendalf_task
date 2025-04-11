<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250410164906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users and sessions tables with relations';
    }

    public function up(Schema $schema): void
    {
        // Создание таблицы users
        $this->addSql('
            CREATE TABLE users (
                id INT AUTO_INCREMENT NOT NULL,
                login VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                UNIQUE INDEX UNIQ_1483A5E9AA08CB10 (login),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        // Создание таблицы sessions
        $this->addSql('
            CREATE TABLE sessions (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                token VARCHAR(500) NOT NULL,
                created_at DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                INDEX IDX_9A609D13A76ED395 (user_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        // Добавление внешнего ключа
        $this->addSql('
            ALTER TABLE sessions 
            ADD CONSTRAINT FK_9A609D13A76ED395 
            FOREIGN KEY (user_id) 
            REFERENCES users (id)
            ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sessions');
        $this->addSql('DROP TABLE users');
    }
}