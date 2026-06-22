<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250622120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schedule: venues, events, import log';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE schedule_venue (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, slug VARCHAR(128) NOT NULL, name VARCHAR(255) NOT NULL, sort_order INT NOT NULL, INDEX IDX_8E8B4A9F4584665A (product_id), UNIQUE INDEX uniq_product_venue_slug (product_id, slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE schedule_venue ADD CONSTRAINT FK_8E8B4A9F4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE schedule_event (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, venue_id INT NOT NULL, starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, title VARCHAR(512) NOT NULL, event_type VARCHAR(16) NOT NULL, external_key VARCHAR(64) NOT NULL, is_published TINYINT(1) NOT NULL, INDEX IDX_7C3B3B3B4584665A (product_id), INDEX idx_schedule_product_starts (product_id, starts_at), UNIQUE INDEX uniq_schedule_external_key (external_key), INDEX IDX_7C3B3B3B40A73EBA (venue_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE schedule_event ADD CONSTRAINT FK_7C3B3B3B4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE schedule_event ADD CONSTRAINT FK_7C3B3B3B40A73EBA FOREIGN KEY (venue_id) REFERENCES schedule_venue (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE schedule_import (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, imported_at DATETIME NOT NULL, source_hash VARCHAR(64) NOT NULL, event_count INT NOT NULL, venue_count INT NOT NULL, source_url VARCHAR(512) DEFAULT NULL, INDEX IDX_9F8E8A3E4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE schedule_import ADD CONSTRAINT FK_9F8E8A3E4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE schedule_event DROP FOREIGN KEY FK_7C3B3B3B40A73EBA');
        $this->addSql('ALTER TABLE schedule_event DROP FOREIGN KEY FK_7C3B3B3B4584665A');
        $this->addSql('ALTER TABLE schedule_import DROP FOREIGN KEY FK_9F8E8A3E4584665A');
        $this->addSql('ALTER TABLE schedule_venue DROP FOREIGN KEY FK_8E8B4A9F4584665A');

        $this->addSql('DROP TABLE schedule_event');
        $this->addSql('DROP TABLE schedule_import');
        $this->addSql('DROP TABLE schedule_venue');
    }
}
