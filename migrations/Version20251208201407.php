<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208201407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "posts" (id UUID NOT NULL, title VARCHAR(180) NOT NULL, content VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, media_url VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_885DBAFA2B36786B ON "posts" (title)');
        $this->addSql('CREATE INDEX IDX_885DBAFA9D86650F ON "posts" (user_id_id)');
        $this->addSql('ALTER TABLE "posts" ADD CONSTRAINT FK_885DBAFA9D86650F FOREIGN KEY (user_id_id) REFERENCES "user" (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "posts" DROP CONSTRAINT FK_885DBAFA9D86650F');
        $this->addSql('DROP TABLE "posts"');
    }
}
