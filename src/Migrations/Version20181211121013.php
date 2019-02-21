<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181211121013 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        // Ensure unique.
        $this->addSql('CREATE TABLE search_temp LIKE search');
        $this->addSql('INSERT INTO search_temp SELECT * FROM search GROUP BY is_identifier, is_type');
        $this->addSql('DROP TABLE search');
        $this->addSql('ALTER TABLE search_temp RENAME TO search');

        $this->addSql('ALTER TABLE search ADD CONSTRAINT FK_B4F0DBA7953C1C61 FOREIGN KEY (source_id) REFERENCES source (id)');
        $this->addSql('CREATE UNIQUE INDEX record_unique ON search (is_type, is_identifier)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE search DROP FOREIGN KEY FK_B4F0DBA7953C1C61');
        $this->addSql('DROP INDEX record_unique ON search');
    }
}
