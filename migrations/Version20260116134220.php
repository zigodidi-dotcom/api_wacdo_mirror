<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260116134220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D3B1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id)');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D357889920 FOREIGN KEY (fonction_id) REFERENCES fonction (id)');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D3A848E3B1 FOREIGN KEY (collaborateur_id) REFERENCES collaborateur (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D3B1E7706E');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D357889920');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D3A848E3B1');
    }
}
