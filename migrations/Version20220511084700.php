<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220511084700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_groupe DROP FOREIGN KEY FK_6ED77F4F26B4F5');
        $this->addSql('ALTER TABLE commande_groupe ADD CONSTRAINT FK_6ED77F4F26B4F5 FOREIGN KEY (lieu_livraison_id) REFERENCES lieu_livraison (id)');
        $this->addSql('ALTER TABLE eleve DROP FOREIGN KEY FK_ECA105F788DC544F');
        $this->addSql('ALTER TABLE eleve ADD code_barre_eleve VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE eleve ADD CONSTRAINT FK_ECA105F788DC544F FOREIGN KEY (compte_eleve_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_groupe DROP FOREIGN KEY FK_6ED77F4F26B4F5');
        $this->addSql('ALTER TABLE commande_groupe ADD CONSTRAINT FK_6ED77F4F26B4F5 FOREIGN KEY (lieu_livraison_id) REFERENCES lieu_livraison (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE eleve DROP FOREIGN KEY FK_ECA105F788DC544F');
        $this->addSql('ALTER TABLE eleve DROP code_barre_eleve');
        $this->addSql('ALTER TABLE eleve ADD CONSTRAINT FK_ECA105F788DC544F FOREIGN KEY (compte_eleve_id) REFERENCES user (id) ON UPDATE CASCADE');
    }
}
