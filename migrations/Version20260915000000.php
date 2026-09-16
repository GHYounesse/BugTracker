<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds attributes needed to round out the core entities into a more
 * complete bug tracker: due dates/environment on issues, a project
 * key/description/lifecycle/owner, category colors, user email/profile
 * fields, and comment edit tracking. Also widens a few VARCHAR(255)
 * free-text columns (issue.description, issue.steps_to_reproduce,
 * comment.content) to TEXT since they were silently truncating content.
 */
final class Version20260915000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add due date/environment to issue, key/description/archived/owner to project, color/description to category, email/display name to user, and updated_at to comment; widen truncated text columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE issue ADD due_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE issue ADD resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE issue ADD environment VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE issue ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE issue ALTER steps_to_reproduce TYPE TEXT');

        $this->addSql('ALTER TABLE project ADD key VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD description TEXT DEFAULT NULL');
        // DEFAULT NOW()/false backfills existing rows; dropped right after so
        // the column matches the mapping exactly (new rows always get a value
        // from the entity constructor, going through the ORM)
        $this->addSql('ALTER TABLE project ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE project ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE project ADD archived BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE project ALTER archived DROP DEFAULT');
        $this->addSql('ALTER TABLE project ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE7E3C61F9 ON project (owner_id)');
        // a plain UNIQUE index already allows multiple NULLs in Postgres, so
        // "no key yet" on several projects is fine without a partial index
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2FB3D0EE8A90ABA9 ON project (key)');

        $this->addSql('ALTER TABLE category ADD color VARCHAR(7) DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD description TEXT DEFAULT NULL');

        $this->addSql('ALTER TABLE "user" ADD email VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD display_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE "user" ALTER created_at DROP DEFAULT');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');

        $this->addSql('ALTER TABLE comment ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE comment ALTER content TYPE TEXT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment DROP updated_at');
        $this->addSql('ALTER TABLE comment ALTER content TYPE VARCHAR(255)');

        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74');
        $this->addSql('ALTER TABLE "user" DROP email');
        $this->addSql('ALTER TABLE "user" DROP display_name');
        $this->addSql('ALTER TABLE "user" DROP created_at');

        $this->addSql('ALTER TABLE category DROP color');
        $this->addSql('ALTER TABLE category DROP description');

        $this->addSql('DROP INDEX UNIQ_2FB3D0EE8A90ABA9');
        $this->addSql('DROP INDEX IDX_2FB3D0EE7E3C61F9');
        $this->addSql('ALTER TABLE project DROP CONSTRAINT FK_2FB3D0EE7E3C61F9');
        $this->addSql('ALTER TABLE project DROP owner_id');
        $this->addSql('ALTER TABLE project DROP archived');
        $this->addSql('ALTER TABLE project DROP created_at');
        $this->addSql('ALTER TABLE project DROP description');
        $this->addSql('ALTER TABLE project DROP key');

        $this->addSql('ALTER TABLE issue ALTER steps_to_reproduce TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE issue ALTER description TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE issue DROP environment');
        $this->addSql('ALTER TABLE issue DROP resolved_at');
        $this->addSql('ALTER TABLE issue DROP due_date');
    }
}
