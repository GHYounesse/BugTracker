<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Introduces project-scoped RBAC: a project_member join table carrying a
 * role (admin/manager/member) per (project, user) pair. Existing project
 * owners are backfilled as "admin" members, and existing issue
 * reporters/assignees as "member"s, so nobody loses access to a project
 * they already own or have filed/been assigned issues against once the
 * new members-only visibility rule goes live.
 */
final class Version20260916000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add project_member table for project-scoped roles, backfilling existing project owners as admins';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE project_member_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE project_member (id INT NOT NULL, project_id INT NOT NULL, user_id INT NOT NULL, role VARCHAR(20) NOT NULL, added_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX project_member_project_user_unique ON project_member (project_id, user_id)');
        $this->addSql('CREATE INDEX IDX_67401132166D1F9C ON project_member (project_id)');
        $this->addSql('CREATE INDEX IDX_67401132A76ED395 ON project_member (user_id)');
        $this->addSql('ALTER TABLE project_member ADD CONSTRAINT FK_67401132166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_member ADD CONSTRAINT FK_67401132A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // grandfather existing project owners in as admins of their own project
        // (id has no column default - GENERATED VIA the ORM's IDENTITY strategy elsewhere -
        // so backfill inserts must pull the next value from the sequence explicitly)
        $this->addSql("INSERT INTO project_member (id, project_id, user_id, role, added_at) SELECT nextval('project_member_id_seq'), id, owner_id, 'admin', NOW() FROM project WHERE owner_id IS NOT NULL");

        // grandfather existing issue reporters/assignees in as members, so they don't lose
        // access to bugs they already filed/were assigned just because the project has no
        // owner on record; ON CONFLICT DO NOTHING skips anyone already added as owner above
        $this->addSql("INSERT INTO project_member (id, project_id, user_id, role, added_at) SELECT nextval('project_member_id_seq'), project_id, reporter_id, 'member', NOW() FROM (SELECT DISTINCT project_id, reporter_id FROM issue) s ON CONFLICT (project_id, user_id) DO NOTHING");
        $this->addSql("INSERT INTO project_member (id, project_id, user_id, role, added_at) SELECT nextval('project_member_id_seq'), project_id, assigned_id, 'member', NOW() FROM (SELECT DISTINCT project_id, assigned_id FROM issue WHERE assigned_id IS NOT NULL) s ON CONFLICT (project_id, user_id) DO NOTHING");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_member DROP CONSTRAINT FK_67401132166D1F9C');
        $this->addSql('ALTER TABLE project_member DROP CONSTRAINT FK_67401132A76ED395');
        $this->addSql('DROP TABLE project_member');
        $this->addSql('DROP SEQUENCE project_member_id_seq CASCADE');
    }
}
