<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230516072722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE issue_issue (issue_source INT NOT NULL, issue_target INT NOT NULL, PRIMARY KEY(issue_source, issue_target))');
        $this->addSql('CREATE INDEX IDX_80274A6DAD7AF554 ON issue_issue (issue_source)');
        $this->addSql('CREATE INDEX IDX_80274A6DB49FA5DB ON issue_issue (issue_target)');
        $this->addSql('ALTER TABLE issue_issue ADD CONSTRAINT FK_80274A6DAD7AF554 FOREIGN KEY (issue_source) REFERENCES issue (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE issue_issue ADD CONSTRAINT FK_80274A6DB49FA5DB FOREIGN KEY (issue_target) REFERENCES issue (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE issue_issue DROP CONSTRAINT FK_80274A6DAD7AF554');
        $this->addSql('ALTER TABLE issue_issue DROP CONSTRAINT FK_80274A6DB49FA5DB');
        $this->addSql('DROP TABLE issue_issue');
        $this->addSql('ALTER TABLE "user" DROP image');
    }
}
