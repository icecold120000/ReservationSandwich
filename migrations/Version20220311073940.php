<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220311073940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_groupe DROP FOREIGN KEY FK_6ED77F43923C156');
        $this->addSql('DROP INDEX IDX_6ED77F43923C156 ON commande_groupe');
        $this->addSql('ALTER TABLE commande_groupe CHANGE boisson_choisi_id boisson_choisie_id INT NOT NULL');
        $this->addSql('ALTER TABLE commande_groupe ADD CONSTRAINT FK_6ED77F410326266 FOREIGN KEY (boisson_choisie_id) REFERENCES boisson (id)');
        $this->addSql('CREATE INDEX IDX_6ED77F410326266 ON commande_groupe (boisson_choisie_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_groupe DROP FOREIGN KEY FK_6ED77F410326266');
        $this->addSql('DROP INDEX IDX_6ED77F410326266 ON commande_groupe');
        $this->addSql('ALTER TABLE commande_groupe CHANGE boisson_choisie_id boisson_choisi_id INT NOT NULL');
        $this->addSql('ALTER TABLE commande_groupe ADD CONSTRAINT FK_6ED77F43923C156 FOREIGN KEY (boisson_choisi_id) REFERENCES boisson (id)');
        $this->addSql('CREATE INDEX IDX_6ED77F43923C156 ON commande_groupe (boisson_choisi_id)');
    }
}
