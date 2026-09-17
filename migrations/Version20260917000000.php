<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the reset_password_request table used by SymfonyCasts's
 * ResetPasswordBundle to back the new "forgot password" flow.
 */
final class Version20260917000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reset_password_request table for the password reset flow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE reset_password_request_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE reset_password_request (id INT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7CE748AA76ED395 ON reset_password_request (user_id)');
        $this->addSql("COMMENT ON COLUMN reset_password_request.requested_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN reset_password_request.expires_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reset_password_request DROP CONSTRAINT FK_7CE748AA76ED395');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP SEQUENCE reset_password_request_id_seq CASCADE');
    }
}
