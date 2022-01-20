<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220119135159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE boisson (id INT AUTO_INCREMENT NOT NULL, nom_boisson VARCHAR(255) NOT NULL, image_boisson VARCHAR(255) NOT NULL, dispo_boisson TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dessert (id INT AUTO_INCREMENT NOT NULL, nom_dessert VARCHAR(255) NOT NULL, image_dessert VARCHAR(255) NOT NULL, ingredient_dessert VARCHAR(255) NOT NULL, commentaire_dessert VARCHAR(255) NOT NULL, dispo_dessert TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sandwich (id INT AUTO_INCREMENT NOT NULL, nom_sandwich VARCHAR(255) NOT NULL, image_sandwich VARCHAR(255) NOT NULL, ingredient_sandwich VARCHAR(255) NOT NULL, commentaire_sandwich VARCHAR(255) NOT NULL, disponible_sandwich TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE boisson');
        $this->addSql('DROP TABLE dessert');
        $this->addSql('DROP TABLE sandwich');
    }
}
