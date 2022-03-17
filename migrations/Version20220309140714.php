<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220309140714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commande_groupe (id INT AUTO_INCREMENT NOT NULL, boisson_choisi_id INT NOT NULL, dessert_choisi_id INT NOT NULL, prendre_chips TINYINT(1) NOT NULL, commentaire_commande LONGTEXT DEFAULT NULL, motif_sortie LONGTEXT NOT NULL, date_heure_livraison DATETIME NOT NULL, lieu_livraison VARCHAR(255) NOT NULL, INDEX IDX_6ED77F43923C156 (boisson_choisi_id), INDEX IDX_6ED77F483C52771 (dessert_choisi_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sandwich_commande_groupe (id INT AUTO_INCREMENT NOT NULL, sandwich_choisi_id INT NOT NULL, commande_affecte_id INT NOT NULL, nombre_sandwich INT NOT NULL, INDEX IDX_AC2D6A35CF8EC6B0 (sandwich_choisi_id), INDEX IDX_AC2D6A35B2D43966 (commande_affecte_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commande_groupe ADD CONSTRAINT FK_6ED77F43923C156 FOREIGN KEY (boisson_choisi_id) REFERENCES boisson (id)');
        $this->addSql('ALTER TABLE commande_groupe ADD CONSTRAINT FK_6ED77F483C52771 FOREIGN KEY (dessert_choisi_id) REFERENCES dessert (id)');
        $this->addSql('ALTER TABLE sandwich_commande_groupe ADD CONSTRAINT FK_AC2D6A35CF8EC6B0 FOREIGN KEY (sandwich_choisi_id) REFERENCES sandwich (id)');
        $this->addSql('ALTER TABLE sandwich_commande_groupe ADD CONSTRAINT FK_AC2D6A35B2D43966 FOREIGN KEY (commande_affecte_id) REFERENCES commande_groupe (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sandwich_commande_groupe DROP FOREIGN KEY FK_AC2D6A35B2D43966');
        $this->addSql('DROP TABLE commande_groupe');
        $this->addSql('DROP TABLE sandwich_commande_groupe');
    }
}
