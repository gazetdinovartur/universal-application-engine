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
        $this->addSql('CREATE TABLE product (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD989D9B62 ON product (slug)');

        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(32) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');

        $this->addSql('CREATE TABLE pricing_period (id SERIAL NOT NULL, product_id INT NOT NULL, name VARCHAR(255) NOT NULL, start_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_882F0C54584665A ON pricing_period (product_id)');
        $this->addSql('ALTER TABLE pricing_period ADD CONSTRAINT FK_882F0C54584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE participation_option (id SERIAL NOT NULL, product_id INT NOT NULL, code VARCHAR(64) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8F5E8B0A4584665A ON participation_option (product_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_product_code ON participation_option (product_id, code)');
        $this->addSql('ALTER TABLE participation_option ADD CONSTRAINT FK_8F5E8B0A4584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE participation_price (id SERIAL NOT NULL, pricing_period_id INT NOT NULL, participation_option_id INT NOT NULL, price INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6F8F8F5E7F966F7 ON participation_price (pricing_period_id)');
        $this->addSql('CREATE INDEX IDX_6F8F8F5E8F5E8B0A ON participation_price (participation_option_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_period_option ON participation_price (pricing_period_id, participation_option_id)');
        $this->addSql('ALTER TABLE participation_price ADD CONSTRAINT FK_6F8F8F5E7F966F7 FOREIGN KEY (pricing_period_id) REFERENCES pricing_period (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participation_price ADD CONSTRAINT FK_6F8F8F5E8F5E8B0A FOREIGN KEY (participation_option_id) REFERENCES participation_option (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE application (id SERIAL NOT NULL, user_id INT NOT NULL, product_id INT NOT NULL, pricing_period_id INT NOT NULL, uuid UUID NOT NULL, status VARCHAR(255) NOT NULL, total_amount INT NOT NULL, paid_amount INT NOT NULL, payload JSONB NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A45BDDC1D17F50A6 ON application (uuid)');
        $this->addSql('CREATE INDEX IDX_A45BDDC1A76ED395 ON application (user_id)');
        $this->addSql('CREATE INDEX IDX_A45BDDC14584665A ON application (product_id)');
        $this->addSql('CREATE INDEX IDX_A45BDDC17F966F7 ON application (pricing_period_id)');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC14584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC17F966F7 FOREIGN KEY (pricing_period_id) REFERENCES pricing_period (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE payment (id SERIAL NOT NULL, application_id INT NOT NULL, provider VARCHAR(255) NOT NULL, provider_payment_id VARCHAR(255) DEFAULT NULL, amount INT NOT NULL, status VARCHAR(255) NOT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6D28840D3E030ACD ON payment (application_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_provider_payment ON payment (provider, provider_payment_id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D3E030ACD FOREIGN KEY (application_id) REFERENCES application (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE payment_link (id SERIAL NOT NULL, application_id INT NOT NULL, token VARCHAR(64) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_172E3D8B5F37A13B ON payment_link (token)');
        $this->addSql('CREATE INDEX IDX_172E3D8B3E030ACD ON payment_link (application_id)');
        $this->addSql('ALTER TABLE payment_link ADD CONSTRAINT FK_172E3D8B3E030ACD FOREIGN KEY (application_id) REFERENCES application (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment_link DROP CONSTRAINT FK_172E3D8B3E030ACD');
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT FK_6D28840D3E030ACD');
        $this->addSql('ALTER TABLE application DROP CONSTRAINT FK_A45BDDC17F966F7');
        $this->addSql('ALTER TABLE application DROP CONSTRAINT FK_A45BDDC14584665A');
        $this->addSql('ALTER TABLE application DROP CONSTRAINT FK_A45BDDC1A76ED395');
        $this->addSql('ALTER TABLE participation_price DROP CONSTRAINT FK_6F8F8F5E8F5E8B0A');
        $this->addSql('ALTER TABLE participation_price DROP CONSTRAINT FK_6F8F8F5E7F966F7');
        $this->addSql('ALTER TABLE participation_option DROP CONSTRAINT FK_8F5E8B0A4584665A');
        $this->addSql('ALTER TABLE pricing_period DROP CONSTRAINT FK_882F0C54584665A');

        $this->addSql('DROP TABLE payment_link');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE application');
        $this->addSql('DROP TABLE participation_price');
        $this->addSql('DROP TABLE participation_option');
        $this->addSql('DROP TABLE pricing_period');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE product');
    }
}
