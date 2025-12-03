<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251125000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create anonymous messaging system tables';
    }

    public function up(Schema $schema): void
    {
        // Anonymous Users Table
        $this->addSql('CREATE TABLE anonymous_users (
            id BIGINT AUTO_INCREMENT NOT NULL,
            anonymous_id VARCHAR(64) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            public_key TEXT NOT NULL,
            recovery_phrase_hash VARCHAR(64) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            messages_sent_count INT NOT NULL DEFAULT 0,
            messages_received_count INT NOT NULL DEFAULT 0,
            UNIQUE INDEX UNIQ_anonymous_id (anonymous_id),
            UNIQUE INDEX UNIQ_recovery_hash (recovery_phrase_hash),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Direct Messages Table
        $this->addSql('CREATE TABLE direct_messages (
            id BIGINT AUTO_INCREMENT NOT NULL,
            message_id VARCHAR(64) NOT NULL,
            sender_id VARCHAR(64) NOT NULL,
            recipient_id VARCHAR(64) NOT NULL,
            encrypted_content TEXT NOT NULL,
            encrypted_key_for_recipient TEXT NOT NULL,
            encrypted_key_for_sender TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            read_at DATETIME NULL,
            is_deleted TINYINT(1) NOT NULL DEFAULT 0,
            UNIQUE INDEX UNIQ_message_id (message_id),
            INDEX IDX_sender (sender_id, created_at),
            INDEX IDX_recipient (recipient_id, is_read, created_at),
            INDEX IDX_expires (expires_at),
            INDEX IDX_thread (sender_id, recipient_id, created_at),
            CONSTRAINT FK_sender FOREIGN KEY (sender_id) REFERENCES anonymous_users (anonymous_id) ON DELETE CASCADE,
            CONSTRAINT FK_recipient FOREIGN KEY (recipient_id) REFERENCES anonymous_users (anonymous_id) ON DELETE CASCADE,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // User Sessions Table
        $this->addSql('CREATE TABLE user_sessions (
            id BIGINT AUTO_INCREMENT NOT NULL,
            session_token VARCHAR(128) NOT NULL,
            user_id VARCHAR(64) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_session_token (session_token),
            INDEX IDX_user_sessions (user_id, expires_at),
            CONSTRAINT FK_session_user FOREIGN KEY (user_id) REFERENCES anonymous_users (anonymous_id) ON DELETE CASCADE,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // User Contacts Table
        $this->addSql('CREATE TABLE user_contacts (
            id BIGINT AUTO_INCREMENT NOT NULL,
            user_id VARCHAR(64) NOT NULL,
            contact_id VARCHAR(64) NOT NULL,
            nickname VARCHAR(100) NULL,
            added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE INDEX UNIQ_user_contact (user_id, contact_id),
            INDEX IDX_user_contacts (user_id),
            CONSTRAINT FK_contact_user FOREIGN KEY (user_id) REFERENCES anonymous_users (anonymous_id) ON DELETE CASCADE,
            CONSTRAINT FK_contact_contact FOREIGN KEY (contact_id) REFERENCES anonymous_users (anonymous_id) ON DELETE CASCADE,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_contacts');
        $this->addSql('DROP TABLE user_sessions');
        $this->addSql('DROP TABLE direct_messages');
        $this->addSql('DROP TABLE anonymous_users');
    }
}

