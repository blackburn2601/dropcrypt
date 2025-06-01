<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250528162406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX uniq_b6bd307f2b36786b
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message ADD encryption_key_hash VARCHAR(64) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message ALTER id DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message ALTER expires_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN message.expires_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN message.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message DROP encryption_key_hash
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE message_id_seq
        SQL);
        $this->addSql(<<<'SQL'
            SELECT setval('message_id_seq', (SELECT MAX(id) FROM message))
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message ALTER id SET DEFAULT nextval('message_id_seq')
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message ALTER expires_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN message.expires_at IS NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN message.created_at IS NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_b6bd307f2b36786b ON message (access_token)
        SQL);
    }
}
