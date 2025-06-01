<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240320000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create messages table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE message (
            id SERIAL NOT NULL,
            encrypted_content VARCHAR(255) NOT NULL,
            access_token VARCHAR(64) NOT NULL,
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            is_viewed BOOLEAN NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B6BD307F2B36786B ON message (access_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE message');
    }
} 