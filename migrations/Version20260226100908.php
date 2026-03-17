<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260226100908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE collaborateur CHANGE date_embauche dateembauche DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE affectation add status BOOLEAN NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE collaborateur CHANGE dateembauche date_embauche DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE affectation DROP status');
    }
}
