<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220324093708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_groupe ADD CONSTRAINT FK_6ED77F4F26B4F5 FOREIGN KEY (lieu_livraison_id) REFERENCES lieu_livraison (id)');
        $this->addSql('CREATE INDEX IDX_6ED77F4F26B4F5 ON commande_groupe (lieu_livraison_id)');
        $this->addSql('ALTER TABLE lieu_livraison ADD est_active TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_groupe DROP FOREIGN KEY FK_6ED77F4F26B4F5');
        $this->addSql('DROP INDEX IDX_6ED77F4F26B4F5 ON commande_groupe');
        $this->addSql('ALTER TABLE lieu_livraison DROP est_active');
    }
}
