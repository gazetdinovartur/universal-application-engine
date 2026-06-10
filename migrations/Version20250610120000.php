<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250610120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make payment.application_id nullable for legacy-compatible payments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment MODIFY application_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment MODIFY application_id INT NOT NULL');
    }
}
