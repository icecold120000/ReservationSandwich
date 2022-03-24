<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220324092644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE lieu_livraison (id INT AUTO_INCREMENT NOT NULL, libelle_lieu VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commande_groupe ADD lieu_livraison_id INT NOT NULL, DROP lieu_livraison');
        $this->addSql('ALTER TABLE commande_groupe ADD CONSTRAINT FK_6ED77F4F26B4F5 FOREIGN KEY (lieu_livraison_id) REFERENCES lieu_livraison (id)');
        $this->addSql('CREATE INDEX IDX_6ED77F4F26B4F5 ON commande_groupe (lieu_livraison_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_groupe DROP FOREIGN KEY FK_6ED77F4F26B4F5');
        $this->addSql('DROP TABLE lieu_livraison');
        $this->addSql('DROP INDEX IDX_6ED77F4F26B4F5 ON commande_groupe');
        $this->addSql('ALTER TABLE commande_groupe ADD lieu_livraison VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP lieu_livraison_id');
    }
}
