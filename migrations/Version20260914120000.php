<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renames the domain model from French to English field names
 * (etat -> status, priorite -> priority, rapporteur -> reporter, etc.),
 * renames the "activite" table/entity to "comment", and fixes the
 * "attachment" column (formerly "tags") being NOT NULL despite issues
 * being submittable without a file.
 */
final class Version20260914120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename French domain fields to English and rename activite -> comment';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activite RENAME TO "comment"');
        $this->addSql('ALTER SEQUENCE activite_id_seq RENAME TO comment_id_seq');
        $this->addSql('ALTER TABLE "comment" RENAME COLUMN commentor_id TO author_id');
        $this->addSql('ALTER TABLE "comment" RENAME COLUMN date TO created_at');

        $this->addSql('ALTER TABLE issue RENAME COLUMN rapporteur_id TO reporter_id');
        $this->addSql('ALTER TABLE issue RENAME COLUMN visibilite TO visibility');
        $this->addSql('ALTER TABLE issue RENAME COLUMN date_soumission TO submitted_at');
        $this->addSql('ALTER TABLE issue RENAME COLUMN date_mise_jour TO updated_at');
        $this->addSql('ALTER TABLE issue RENAME COLUMN priorite TO priority');
        $this->addSql('ALTER TABLE issue RENAME COLUMN severite TO severity');
        $this->addSql('ALTER TABLE issue RENAME COLUMN reproduce TO steps_to_reproduce');
        $this->addSql('ALTER TABLE issue RENAME COLUMN etat TO status');
        $this->addSql('ALTER TABLE issue RENAME COLUMN resume TO summary');
        $this->addSql('ALTER TABLE issue RENAME COLUMN tags TO attachment');
        $this->addSql('ALTER TABLE issue ALTER COLUMN attachment DROP NOT NULL');

        $this->addSql("UPDATE issue SET priority = CASE priority WHEN 'basse' THEN 'low' WHEN 'normale' THEN 'normal' WHEN 'élevée' THEN 'high' WHEN 'urgente' THEN 'urgent' ELSE priority END");
        $this->addSql("UPDATE issue SET severity = CASE severity WHEN 'simple' THEN 'trivial' WHEN 'mineur' THEN 'minor' WHEN 'majeur' THEN 'major' WHEN 'critique' THEN 'critical' WHEN 'bloquant' THEN 'blocker' ELSE severity END");
        $this->addSql("UPDATE issue SET status = CASE status WHEN 'nouveau' THEN 'new' WHEN 'accepté' THEN 'accepted' WHEN 'confirmé' THEN 'confirmed' WHEN 'affecté' THEN 'assigned' WHEN 'traité' THEN 'processed' WHEN 'fermé' THEN 'closed' ELSE status END");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE issue SET priority = CASE priority WHEN 'low' THEN 'basse' WHEN 'normal' THEN 'normale' WHEN 'high' THEN 'élevée' WHEN 'urgent' THEN 'urgente' ELSE priority END");
        $this->addSql("UPDATE issue SET severity = CASE severity WHEN 'trivial' THEN 'simple' WHEN 'minor' THEN 'mineur' WHEN 'major' THEN 'majeur' WHEN 'critical' THEN 'critique' WHEN 'blocker' THEN 'bloquant' ELSE severity END");
        $this->addSql("UPDATE issue SET status = CASE status WHEN 'new' THEN 'nouveau' WHEN 'accepted' THEN 'accepté' WHEN 'confirmed' THEN 'confirmé' WHEN 'assigned' THEN 'affecté' WHEN 'processed' THEN 'traité' WHEN 'closed' THEN 'fermé' ELSE status END");

        $this->addSql('ALTER TABLE issue ALTER COLUMN attachment SET NOT NULL');
        $this->addSql('ALTER TABLE issue RENAME COLUMN attachment TO tags');
        $this->addSql('ALTER TABLE issue RENAME COLUMN summary TO resume');
        $this->addSql('ALTER TABLE issue RENAME COLUMN status TO etat');
        $this->addSql('ALTER TABLE issue RENAME COLUMN steps_to_reproduce TO reproduce');
        $this->addSql('ALTER TABLE issue RENAME COLUMN severity TO severite');
        $this->addSql('ALTER TABLE issue RENAME COLUMN priority TO priorite');
        $this->addSql('ALTER TABLE issue RENAME COLUMN updated_at TO date_mise_jour');
        $this->addSql('ALTER TABLE issue RENAME COLUMN submitted_at TO date_soumission');
        $this->addSql('ALTER TABLE issue RENAME COLUMN visibility TO visibilite');
        $this->addSql('ALTER TABLE issue RENAME COLUMN reporter_id TO rapporteur_id');

        $this->addSql('ALTER TABLE "comment" RENAME COLUMN created_at TO date');
        $this->addSql('ALTER TABLE "comment" RENAME COLUMN author_id TO commentor_id');
        $this->addSql('ALTER SEQUENCE comment_id_seq RENAME TO activite_id_seq');
        $this->addSql('ALTER TABLE "comment" RENAME TO activite');
    }
}
