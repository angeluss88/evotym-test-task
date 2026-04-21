<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421153846 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the products and orders tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE orders (id UUID NOT NULL, customer_name VARCHAR(255) NOT NULL, quantity_ordered INT NOT NULL, order_status VARCHAR(50) NOT NULL, product_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_E52FFDEE4584665A ON orders (product_id)');
        $this->addSql('CREATE TABLE products (id UUID NOT NULL, name VARCHAR(255) NOT NULL, price NUMERIC(10, 2) NOT NULL, quantity INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE4584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP CONSTRAINT FK_E52FFDEE4584665A');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE products');
    }
}
