<?php

namespace HeliomarPM\ErrorTracker\Handlers;

use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use HeliomarPM\ErrorTracker\ErrorContext;
use PDO;
use PDOException;

class DatabaseHandler implements HandlerInterface
{
  private bool $tableChecked = false;

  /**
   * @param PDO $pdo Instância de conexão PDO.
   * @param string $tableName Nome da tabela de erros.
   * @param bool $autoCreate Se true, tenta criar a tabela automaticamente caso não exista.
   */
  public function __construct(
      private readonly PDO $pdo,
      private readonly string $tableName = "error_tracker",
      private readonly bool $autoCreate = false
  ) {}

  public function report(ErrorContext $context): void
  {
    // Só tenta criar a tabela se a flag estiver ativa e se ainda não verificou nesta execução
    if ($this->autoCreate && !$this->tableChecked) {
      $this->createTableIfNotExists();
      $this->tableChecked = true;
    }

    $driver = (string)$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $sql = $this->buildInsertQuery($driver);

    try {
      $stmt = $this->pdo->prepare($sql);

      $payloadJson = json_encode($context->requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';

      $stmt->execute([
          ':hash' => $context->hash,
          ':severity' => $context->severity->value,
          ':message' => $context->message,
          ':file' => $context->file,
          ':line' => $context->line,
          ':trace' => $context->trace,
          ':payload' => $payloadJson,
      ]);
    } catch (PDOException $e) {
      error_log("HeliomarPM ErrorTracker DatabaseHandler falhou ao inserir erro:\n[{$e->getLine()}] {$e->getMessage()}");
    }
  }

  private function buildInsertQuery(string $driver): string
  {
    if ($driver === 'sqlite') {
      return "INSERT INTO {$this->tableName}
              (hash, severity, message, file, line, trace, payload, created_at, updated_at, incidents)
              VALUES
              (:hash, :severity, :message, :file, :line, :trace, :payload, datetime('now'), datetime('now'), 1)
              ON CONFLICT(hash) DO UPDATE SET updated_at = datetime('now'), incidents = incidents + 1;";
    }

    return "INSERT INTO {$this->tableName}
            (hash, severity, message, file, line, trace, payload, created_at, updated_at, incidents)
            VALUES
            (:hash, :severity, :message, :file, :line, :trace, :payload, NOW(), NOW(), 1)
            ON DUPLICATE KEY UPDATE updated_at = NOW(), incidents = incidents + 1;";
  }

  private function createTableIfNotExists(): void
  {
    $driver = (string)$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
      $sql = "CREATE TABLE IF NOT EXISTS `{$this->tableName}` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `hash` VARCHAR(32) NOT NULL,
              `severity` VARCHAR(20) NOT NULL,
              `message` TEXT NOT NULL,
              `file` VARCHAR(255) NOT NULL,
              `line` INT NOT NULL,
              `trace` TEXT NOT NULL,
              `payload` TEXT NOT NULL,
              `created_at` DATETIME NOT NULL,
              `updated_at` DATETIME NOT NULL,
              `incidents` INT DEFAULT 1,
              CONSTRAINT `uq_hash` UNIQUE (`hash`)
          );
          CREATE INDEX IF NOT EXISTS `idx_severity` ON `{$this->tableName}` (`severity`);";
    } else {
      // Sintaxe compatível com MySQL / MariaDB
      $sql = "CREATE TABLE IF NOT EXISTS `{$this->tableName}` (
              `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
              `hash` VARCHAR(32) NOT NULL,
              `severity` VARCHAR(20) NOT NULL,
              `message` TEXT NOT NULL,
              `file` VARCHAR(255) NOT NULL,
              `line` INT NOT NULL,
              `trace` LONGTEXT NOT NULL,
              `payload` JSON NOT NULL,
              `created_at` DATETIME NOT NULL,
              `updated_at` DATETIME NOT NULL,
              `incidents` INT DEFAULT 1,
              UNIQUE KEY `uq_hash` (`hash`),
              INDEX `idx_severity` (`severity`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    }

    try {
      $this->pdo->exec($sql);
    } catch (PDOException $e) {
      // Se o usuário do banco não tiver permissão de DDL (CREATE TABLE),
      // registramos silenciosamente para não quebrar a aplicação principal.
      error_log("HeliomarPM ErrorTracker DatabaseHandler falhou ao criar tabela '{$this->tableName}':\n[{$e->getLine()}] {$e->getMessage()}");
    }
  }
}
