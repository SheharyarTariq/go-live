<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251123160913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Drop foreign key constraint first before dropping the plan table
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT IF EXISTS fk_8d93d649e899029b');
        $this->addSql('DROP INDEX IF EXISTS idx_8d93d649e899029b');
        $this->addSql('DROP TABLE IF EXISTS plan CASCADE');
        $this->addSql('DROP TABLE IF EXISTS post CASCADE');
        $this->addSql('ALTER TABLE "user" ADD active VARCHAR(20) NOT NULL DEFAULT \'pending\'');
        $this->addSql('ALTER TABLE "user" ADD subscription VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" DROP roles');
        $this->addSql('ALTER TABLE "user" DROP stripe_customer_id');
        $this->addSql('ALTER TABLE "user" DROP stripe_subscription_id');
        $this->addSql('ALTER TABLE "user" DROP is_active');
        $this->addSql('ALTER TABLE "user" DROP plan_id');
        $this->addSql('ALTER TABLE "user" RENAME COLUMN billing_cycle TO role');
        $this->addSql('ALTER TABLE "user" RENAME COLUMN subscription_ends_at TO subscription_end');
        // Set default value for role column (was billing_cycle)
        $this->addSql('UPDATE "user" SET role = \'user\' WHERE role NOT IN (\'user\', \'admin\')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE plan (id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, monthly_price NUMERIC(10, 2) NOT NULL, yearly_price NUMERIC(10, 2) NOT NULL, stripe_monthly_price_id VARCHAR(255) NOT NULL, stripe_yearly_price_id VARCHAR(255) NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE post (id UUID NOT NULL, title VARCHAR(255) NOT NULL, content TEXT NOT NULL, type VARCHAR(50) NOT NULL, video_url VARCHAR(500) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, author_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_5a8a6c8df675f31b ON post (author_id)');
        $this->addSql('ALTER TABLE "user" ADD roles JSON NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD billing_cycle VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD stripe_subscription_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD is_active BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD plan_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" DROP role');
        $this->addSql('ALTER TABLE "user" DROP active');
        $this->addSql('ALTER TABLE "user" RENAME COLUMN subscription TO stripe_customer_id');
        $this->addSql('ALTER TABLE "user" RENAME COLUMN subscription_end TO subscription_ends_at');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT fk_8d93d649e899029b FOREIGN KEY (plan_id) REFERENCES plan (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_8d93d649e899029b ON "user" (plan_id)');
    }
}
