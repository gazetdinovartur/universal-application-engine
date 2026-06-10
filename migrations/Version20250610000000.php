<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250610000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema: users, products, pricing, applications, payments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD989D9B62 ON product (slug)');

        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(32) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON `user` (email)');

        $this->addSql('CREATE TABLE pricing_period (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, name VARCHAR(255) NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_882F0C54584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pricing_period ADD CONSTRAINT FK_882F0C54584665A FOREIGN KEY (product_id) REFERENCES product (id)');

        $this->addSql('CREATE TABLE participation_option (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, code VARCHAR(64) NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_8F5E8B0A4584665A (product_id), UNIQUE INDEX uniq_product_code (product_id, code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE participation_option ADD CONSTRAINT FK_8F5E8B0A4584665A FOREIGN KEY (product_id) REFERENCES product (id)');

        $this->addSql('CREATE TABLE participation_price (id INT AUTO_INCREMENT NOT NULL, pricing_period_id INT NOT NULL, participation_option_id INT NOT NULL, price INT NOT NULL, INDEX IDX_6F8F8F5E7F966F7 (pricing_period_id), INDEX IDX_6F8F8F5E8F5E8B0A (participation_option_id), UNIQUE INDEX uniq_period_option (pricing_period_id, participation_option_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE participation_price ADD CONSTRAINT FK_6F8F8F5E7F966F7 FOREIGN KEY (pricing_period_id) REFERENCES pricing_period (id)');
        $this->addSql('ALTER TABLE participation_price ADD CONSTRAINT FK_6F8F8F5E8F5E8B0A FOREIGN KEY (participation_option_id) REFERENCES participation_option (id)');

        $this->addSql('CREATE TABLE application (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, product_id INT NOT NULL, pricing_period_id INT NOT NULL, uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', status VARCHAR(255) NOT NULL, total_amount INT NOT NULL, paid_amount INT NOT NULL, payload JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_A45BDDC1A76ED395 (user_id), INDEX IDX_A45BDDC14584665A (product_id), INDEX IDX_A45BDDC17F966F7 (pricing_period_id), UNIQUE INDEX UNIQ_A45BDDC1D17F50A6 (uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC14584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC17F966F7 FOREIGN KEY (pricing_period_id) REFERENCES pricing_period (id)');

        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, application_id INT NOT NULL, provider VARCHAR(255) NOT NULL, provider_payment_id VARCHAR(255) DEFAULT NULL, amount INT NOT NULL, status VARCHAR(255) NOT NULL, paid_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_6D28840D3E030ACD (application_id), UNIQUE INDEX uniq_provider_payment (provider, provider_payment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D3E030ACD FOREIGN KEY (application_id) REFERENCES application (id)');

        $this->addSql('CREATE TABLE payment_link (id INT AUTO_INCREMENT NOT NULL, application_id INT NOT NULL, token VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_172E3D8B3E030ACD (application_id), UNIQUE INDEX UNIQ_172E3D8B5F37A13B (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE payment_link ADD CONSTRAINT FK_172E3D8B3E030ACD FOREIGN KEY (application_id) REFERENCES application (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment_link DROP FOREIGN KEY FK_172E3D8B3E030ACD');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D3E030ACD');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC17F966F7');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC14584665A');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC1A76ED395');
        $this->addSql('ALTER TABLE participation_price DROP FOREIGN KEY FK_6F8F8F5E8F5E8B0A');
        $this->addSql('ALTER TABLE participation_price DROP FOREIGN KEY FK_6F8F8F5E7F966F7');
        $this->addSql('ALTER TABLE participation_option DROP FOREIGN KEY FK_8F5E8B0A4584665A');
        $this->addSql('ALTER TABLE pricing_period DROP FOREIGN KEY FK_882F0C54584665A');

        $this->addSql('DROP TABLE payment_link');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE application');
        $this->addSql('DROP TABLE participation_price');
        $this->addSql('DROP TABLE participation_option');
        $this->addSql('DROP TABLE pricing_period');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE product');
    }
}
