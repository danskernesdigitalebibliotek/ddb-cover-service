<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181127175941 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, image_format VARCHAR(4) DEFAULT NULL, size INT NOT NULL, width INT NOT NULL, height INT NOT NULL, cover_store_url LONGTEXT NOT NULL, auto_generated TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE search (id INT AUTO_INCREMENT NOT NULL, source_id INT DEFAULT NULL, is_identifier VARCHAR(50) NOT NULL, is_type VARCHAR(5) NOT NULL, image_url LONGTEXT NOT NULL, image_format VARCHAR(255) NOT NULL, width INT NOT NULL, height INT NOT NULL, auto_generated TINYINT(1) NOT NULL, INDEX IDX_B4F0DBA7953C1C61 (source_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE source (id INT AUTO_INCREMENT NOT NULL, vendor_id INT NOT NULL, image_id INT DEFAULT NULL, date DATETIME NOT NULL, match_id VARCHAR(50) NOT NULL, match_type VARCHAR(25) NOT NULL, original_file LONGTEXT DEFAULT NULL, INDEX IDX_5F8A7F73F603EE73 (vendor_id), UNIQUE INDEX UNIQ_5F8A7F733DA5256D (image_id), INDEX is_type_vendor_idx (match_id, match_type, vendor_id), INDEX is_vendor_idx (match_id, vendor_id), UNIQUE INDEX vendor_unique (vendor_id, match_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vendor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, image_server_uri VARCHAR(255) NOT NULL, data_server_uri VARCHAR(255) NOT NULL, data_server_user VARCHAR(255) NOT NULL, data_server_password VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE search ADD CONSTRAINT FK_B4F0DBA7953C1C61 FOREIGN KEY (source_id) REFERENCES source (id)');
        $this->addSql('ALTER TABLE source ADD CONSTRAINT FK_5F8A7F73F603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id)');
        $this->addSql('ALTER TABLE source ADD CONSTRAINT FK_5F8A7F733DA5256D FOREIGN KEY (image_id) REFERENCES image (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE source DROP FOREIGN KEY FK_5F8A7F733DA5256D');
        $this->addSql('ALTER TABLE search DROP FOREIGN KEY FK_B4F0DBA7953C1C61');
        $this->addSql('ALTER TABLE source DROP FOREIGN KEY FK_5F8A7F73F603EE73');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE search');
        $this->addSql('DROP TABLE source');
        $this->addSql('DROP TABLE vendor');
    }
}
