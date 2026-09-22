<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the notification table (assigned/mentioned/commented/due_soon) and
 * issue.due_soon_notified_at, which the due-date reminder command uses to
 * avoid notifying the same person about the same due date twice.
 */
final class Version20260922141326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notification table and issue.due_soon_notified_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE notification_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE notification (id INT NOT NULL, recipient_id INT NOT NULL, issue_id INT NOT NULL, comment_id INT DEFAULT NULL, actor_id INT DEFAULT NULL, type VARCHAR(20) NOT NULL, actor_username VARCHAR(180) DEFAULT NULL, excerpt VARCHAR(160) DEFAULT NULL, read BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BF5476CAE92F8F78 ON notification (recipient_id)');
        $this->addSql('CREATE INDEX IDX_BF5476CA5E7AA58C ON notification (issue_id)');
        $this->addSql('CREATE INDEX IDX_BF5476CAF8697D13 ON notification (comment_id)');
        $this->addSql('CREATE INDEX IDX_BF5476CA10DAF24A ON notification (actor_id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE92F8F78 FOREIGN KEY (recipient_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA5E7AA58C FOREIGN KEY (issue_id) REFERENCES issue (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAF8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA10DAF24A FOREIGN KEY (actor_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE issue ADD due_soon_notified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP CONSTRAINT FK_BF5476CAE92F8F78');
        $this->addSql('ALTER TABLE notification DROP CONSTRAINT FK_BF5476CA5E7AA58C');
        $this->addSql('ALTER TABLE notification DROP CONSTRAINT FK_BF5476CAF8697D13');
        $this->addSql('ALTER TABLE notification DROP CONSTRAINT FK_BF5476CA10DAF24A');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP SEQUENCE notification_id_seq CASCADE');
        $this->addSql('ALTER TABLE issue DROP due_soon_notified_at');
    }
}
