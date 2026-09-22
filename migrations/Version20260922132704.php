<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds issue_activity, which records status/priority/assignee changes for the
 * issue detail page's activity history.
 */
final class Version20260922132704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add issue_activity table for issue change history';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE issue_activity_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE issue_activity (id INT NOT NULL, issue_id INT NOT NULL, actor_id INT DEFAULT NULL, actor_username VARCHAR(180) NOT NULL, field VARCHAR(20) NOT NULL, old_value VARCHAR(180) DEFAULT NULL, new_value VARCHAR(180) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7BDC23F15E7AA58C ON issue_activity (issue_id)');
        $this->addSql('CREATE INDEX IDX_7BDC23F110DAF24A ON issue_activity (actor_id)');
        $this->addSql('ALTER TABLE issue_activity ADD CONSTRAINT FK_7BDC23F15E7AA58C FOREIGN KEY (issue_id) REFERENCES issue (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE issue_activity ADD CONSTRAINT FK_7BDC23F110DAF24A FOREIGN KEY (actor_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE issue_activity DROP CONSTRAINT FK_7BDC23F15E7AA58C');
        $this->addSql('ALTER TABLE issue_activity DROP CONSTRAINT FK_7BDC23F110DAF24A');
        $this->addSql('DROP TABLE issue_activity');
        $this->addSql('DROP SEQUENCE issue_activity_id_seq CASCADE');
    }
}
