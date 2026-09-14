<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fixes a gap in Version20260914120000: the comment.description column was
 * never renamed to content, even though the Comment entity was already
 * updated to expect it.
 */
final class Version20260914130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename comment.description to comment.content';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment RENAME COLUMN description TO content');
        $this->addSql('ALTER INDEX idx_b8755515fe65b143 RENAME TO IDX_9474526CF675F31B');
        $this->addSql('ALTER INDEX idx_b87555155e7aa58c RENAME TO IDX_9474526C5E7AA58C');
        $this->addSql('ALTER INDEX idx_12ad233e2af5d182 RENAME TO IDX_12AD233EE1CFE6F5');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment RENAME COLUMN content TO description');
        $this->addSql('ALTER INDEX "IDX_9474526CF675F31B" RENAME TO idx_b8755515fe65b143');
        $this->addSql('ALTER INDEX "IDX_9474526C5E7AA58C" RENAME TO idx_b87555155e7aa58c');
        $this->addSql('ALTER INDEX "IDX_12AD233EE1CFE6F5" RENAME TO idx_12ad233e2af5d182');
    }
}
