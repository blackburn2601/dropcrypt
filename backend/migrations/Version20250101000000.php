<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to increase encrypted_content field length to support 10,000 character messages
 */
final class Version20250101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increase encrypted_content field length to support larger messages (up to 10,000 characters)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message ALTER COLUMN encrypted_content TYPE VARCHAR(15000)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message ALTER COLUMN encrypted_content TYPE VARCHAR(255)');
    }
} 