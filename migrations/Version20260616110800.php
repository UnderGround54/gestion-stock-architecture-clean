<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260616110800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoices ADD excl_tax_currency VARCHAR(3) NOT NULL, ADD tax_currency VARCHAR(3) NOT NULL, ADD incl_tax_currency VARCHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE order_lines ADD unit_price_currency VARCHAR(3) NOT NULL, ADD sub_total_currency VARCHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE orders ADD total_currency VARCHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE products ADD price_currency VARCHAR(3) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoices DROP excl_tax_currency, DROP tax_currency, DROP incl_tax_currency');
        $this->addSql('ALTER TABLE order_lines DROP unit_price_currency, DROP sub_total_currency');
        $this->addSql('ALTER TABLE orders DROP total_currency');
        $this->addSql('ALTER TABLE products DROP price_currency');
    }
}
