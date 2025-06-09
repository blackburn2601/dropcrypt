<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250609120944 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add access_token field to message table';
    }

    public function up(Schema $schema): void
    {
        // First add the column as nullable
        $this->addSql('ALTER TABLE message ADD access_token VARCHAR(32) DEFAULT NULL');
        
        // Generate access tokens for existing records
        $this->addSql('UPDATE message SET access_token = md5(random()::text) WHERE access_token IS NULL');
        
        // Make the column non-nullable and add unique constraint
        $this->addSql('ALTER TABLE message ALTER COLUMN access_token SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B6BD307FB6A2DD68 ON message (access_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_B6BD307FB6A2DD68');
        $this->addSql('ALTER TABLE message DROP access_token');
    }
}
