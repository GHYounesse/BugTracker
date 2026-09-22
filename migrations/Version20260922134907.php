<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Replaces the single issue.attachment column with an attachment table, so an
 * issue (or a comment on it) can carry any number of files. Existing single
 * attachments are carried over as issue-level attachments (comment_id null),
 * attributed to the issue's reporter, with a best-effort mime type guessed
 * from the file extension.
 */
final class Version20260922134907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace issue.attachment with a multi-file attachment table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE attachment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE attachment (id INT NOT NULL, issue_id INT NOT NULL, comment_id INT DEFAULT NULL, uploaded_by_id INT DEFAULT NULL, uploaded_by_username VARCHAR(180) NOT NULL, filename VARCHAR(255) NOT NULL, original_filename VARCHAR(255) NOT NULL, mime_type VARCHAR(100) DEFAULT NULL, size INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_795FD9BB5E7AA58C ON attachment (issue_id)');
        $this->addSql('CREATE INDEX IDX_795FD9BBF8697D13 ON attachment (comment_id)');
        $this->addSql('CREATE INDEX IDX_795FD9BBA2B28FE8 ON attachment (uploaded_by_id)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB5E7AA58C FOREIGN KEY (issue_id) REFERENCES issue (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBF8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        // carry over existing single attachments before the column disappears
        $this->addSql(<<<'SQL'
            INSERT INTO attachment (id, issue_id, comment_id, uploaded_by_id, uploaded_by_username, filename, original_filename, mime_type, size, created_at)
            SELECT nextval('attachment_id_seq'), i.id, NULL, i.reporter_id, u.username, i.attachment, i.attachment,
                CASE
                    WHEN lower(i.attachment) LIKE '%.jpg' OR lower(i.attachment) LIKE '%.jpeg' THEN 'image/jpeg'
                    WHEN lower(i.attachment) LIKE '%.png' THEN 'image/png'
                    WHEN lower(i.attachment) LIKE '%.gif' THEN 'image/gif'
                    WHEN lower(i.attachment) LIKE '%.pdf' THEN 'application/pdf'
                    ELSE NULL
                END,
                NULL, i.submitted_at
            FROM issue i
            JOIN "user" u ON u.id = i.reporter_id
            WHERE i.attachment IS NOT NULL
            SQL);

        $this->addSql('ALTER TABLE issue DROP attachment');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE issue ADD attachment VARCHAR(255) DEFAULT NULL');

        // best-effort: only the earliest issue-level attachment per issue survives
        // going down, since the old column only ever held one filename
        $this->addSql(<<<'SQL'
            UPDATE issue SET attachment = earliest.filename
            FROM (
                SELECT DISTINCT ON (issue_id) issue_id, filename
                FROM attachment
                WHERE comment_id IS NULL
                ORDER BY issue_id, created_at ASC, id ASC
            ) earliest
            WHERE issue.id = earliest.issue_id
            SQL);

        $this->addSql('ALTER TABLE attachment DROP CONSTRAINT FK_795FD9BB5E7AA58C');
        $this->addSql('ALTER TABLE attachment DROP CONSTRAINT FK_795FD9BBF8697D13');
        $this->addSql('ALTER TABLE attachment DROP CONSTRAINT FK_795FD9BBA2B28FE8');
        $this->addSql('DROP TABLE attachment');
        $this->addSql('DROP SEQUENCE attachment_id_seq CASCADE');
    }
}
