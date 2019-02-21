<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181210174102 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE vendor ADD class VARCHAR(255) NOT NULL AFTER id, CHANGE image_server_uri image_server_uri VARCHAR(255) DEFAULT NULL, CHANGE data_server_uri data_server_uri VARCHAR(255) DEFAULT NULL, CHANGE data_server_user data_server_user VARCHAR(255) DEFAULT NULL, CHANGE data_server_password data_server_password VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE `vendor` SET class = id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F52233F6ED4B199F ON vendor (class)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_F52233F6ED4B199F ON vendor');
        $this->addSql('ALTER TABLE vendor DROP class, CHANGE image_server_uri image_server_uri VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE data_server_uri data_server_uri VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE data_server_user data_server_user VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE data_server_password data_server_password VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci');
    }
}
