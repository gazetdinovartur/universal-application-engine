<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250611000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align application.uuid column type with Doctrine uuid mapping on MySQL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE application MODIFY uuid BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE application MODIFY uuid CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)'");
    }
}

