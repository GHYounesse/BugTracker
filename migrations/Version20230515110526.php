<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230515110526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE activite_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE category_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE issue_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE project_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE activite (id INT NOT NULL, commentor_id INT NOT NULL, issue_id INT NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B8755515FE65B143 ON activite (commentor_id)');
        $this->addSql('CREATE INDEX IDX_B87555155E7AA58C ON activite (issue_id)');
        $this->addSql('CREATE TABLE category (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE issue (id INT NOT NULL, project_id INT NOT NULL, category_id INT NOT NULL, rapporteur_id INT NOT NULL, assigned_id INT DEFAULT NULL, visibilite VARCHAR(255) NOT NULL, date_soumission TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, date_mise_jour TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, priorite VARCHAR(255) NOT NULL, severite VARCHAR(255) NOT NULL, reproduce VARCHAR(255) DEFAULT NULL, etat VARCHAR(255) NOT NULL, resume VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, tags VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_12AD233E166D1F9C ON issue (project_id)');
        $this->addSql('CREATE INDEX IDX_12AD233E12469DE2 ON issue (category_id)');
        $this->addSql('CREATE INDEX IDX_12AD233E2AF5D182 ON issue (rapporteur_id)');
        $this->addSql('CREATE INDEX IDX_12AD233EE1501A05 ON issue (assigned_id)');
        $this->addSql('CREATE TABLE project (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT FK_B8755515FE65B143 FOREIGN KEY (commentor_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT FK_B87555155E7AA58C FOREIGN KEY (issue_id) REFERENCES issue (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE issue ADD CONSTRAINT FK_12AD233E166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE issue ADD CONSTRAINT FK_12AD233E12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE issue ADD CONSTRAINT FK_12AD233E2AF5D182 FOREIGN KEY (rapporteur_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE issue ADD CONSTRAINT FK_12AD233EE1501A05 FOREIGN KEY (assigned_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE activite_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE category_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE issue_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE project_id_seq CASCADE');
        $this->addSql('ALTER TABLE activite DROP CONSTRAINT FK_B8755515FE65B143');
        $this->addSql('ALTER TABLE activite DROP CONSTRAINT FK_B87555155E7AA58C');
        $this->addSql('ALTER TABLE issue DROP CONSTRAINT FK_12AD233E166D1F9C');
        $this->addSql('ALTER TABLE issue DROP CONSTRAINT FK_12AD233E12469DE2');
        $this->addSql('ALTER TABLE issue DROP CONSTRAINT FK_12AD233E2AF5D182');
        $this->addSql('ALTER TABLE issue DROP CONSTRAINT FK_12AD233EE1501A05');
        $this->addSql('DROP TABLE activite');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE issue');
        $this->addSql('DROP TABLE project');
    }
}
