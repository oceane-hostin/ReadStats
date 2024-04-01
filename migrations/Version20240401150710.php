<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240401150710 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book CHANGE is_manga is_manga TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE reading ADD is_ebook TINYINT(1) NOT NULL, CHANGE is_owned is_owned TINYINT(1) NOT NULL, CHANGE is_borrowed is_borrowed TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book CHANGE is_manga is_manga TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE reading DROP is_ebook, CHANGE is_owned is_owned TINYINT(1) DEFAULT 0 NOT NULL, CHANGE is_borrowed is_borrowed TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
