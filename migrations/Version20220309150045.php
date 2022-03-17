<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220309150045 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_groupe ADD commandeur_id INT NOT NULL');
        $this->addSql('ALTER TABLE commande_groupe ADD CONSTRAINT FK_6ED77F4996F9D6F FOREIGN KEY (commandeur_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6ED77F4996F9D6F ON commande_groupe (commandeur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_groupe DROP FOREIGN KEY FK_6ED77F4996F9D6F');
        $this->addSql('DROP INDEX IDX_6ED77F4996F9D6F ON commande_groupe');
        $this->addSql('ALTER TABLE commande_groupe DROP commandeur_id');
    }
}
