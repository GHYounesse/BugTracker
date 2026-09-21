<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds user.active so accounts can be deactivated without deleting the
 * issues and comments they are attached to.
 */
final class Version20260921000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add active flag to user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD active BOOLEAN DEFAULT true NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP active');
    }
}
