<?php

namespace HeliomarPM\ErrorTracker\Handlers;

use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Severity;
use PDO;
use PDOException;
use Stringable;
use Throwable;

class DatabaseHandler implements HandlerInterface
{
  private bool $tableChecked = false;

  /**
   * @param PDO $pdo Instância de conexão PDO.
   * @param string $tableName Nome da tabela de erros.
   * @param bool $autoCreate Se true, tenta criar a tabela automaticamente caso não exista.
   * @param array<Severity>|null $notifySeverities Níveis de severidade que persistem no banco (null persiste todas).
   * @param string|null $userName Nome ou identificador estático do usuário padrão para este handler.
   * @param (callable(): (string|Stringable|null))|null $userResolver Callback dinâmico para resolução do usuário neste handler.
   */
  public function __construct(
      private readonly PDO $pdo,
      private readonly string $tableName = "error_tracker",
      private readonly bool $autoCreate = false,
      private readonly ?array $notifySeverities = null,
      private readonly ?string $userName = null,
      private $userResolver = null
  ) {}

  public function report(ErrorContext $context): void
  {
    // Só tenta criar a tabela se a flag estiver ativa e se ainda não verificou nesta execução
    if ($this->autoCreate && !$this->tableChecked) {
      $this->createTableIfNotExists();
      $this->tableChecked = true;
    }

    if ($this->notifySeverities !== null && !in_array($context->severity, $this->notifySeverities, true)) {
      return;
    }

    $driver = (string)$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $sql = $this->buildInsertQuery($driver);

    try {
      $stmt = $this->pdo->prepare($sql);
      $record = $this->buildRecord($context);

      $stmt->execute([
          ':hash' => $record['hash'],
          ':severity' => $record['severity'],
          ':error_class' => $record['error_class'],
          ':previous_class' => $record['previous_class'],
          ':message' => $record['message'],
          ':file' => $record['file'],
          ':line' => $record['line'],
          ':method' => $record['method'],
          ':uri' => $record['uri'],
          ':user_name' => $record['user_name'],
          ':trace' => $record['trace'],
          ':payload' => $record['payload'],
      ]);
    } catch (PDOException $e) {
      error_log("HeliomarPM ErrorTracker DatabaseHandler falhou ao inserir erro:\n[{$e->getLine()}] {$e->getMessage()}");
    }
  }

  /**
   * Monta o array associativo dos dados estruturados para persistência (público para testes e preview na Demo).
   *
   * @return array<string, mixed>
   */
  public function buildRecord(ErrorContext $context): array
  {
    $payloadJson = json_encode($context->requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';

    $userName = $context->userName;
    if ($userName === null) {
      if ($this->userResolver !== null) {
        try {
          $resolved = ($this->userResolver)();
          if ($resolved !== null && $resolved !== '') {
            $userName = (string)$resolved;
          }
        } catch (Throwable) {
          // Absorve exceções do resolver
        }
      }
      if ($userName === null && $this->userName !== null) {
        $userName = $this->userName;
      }
    }

    return [
        'hash' => $context->hash,
        'severity' => $context->severity->value,
        'error_class' => $context->exceptionClass,
        'previous_class' => $context->previousClass,
        'message' => $context->message,
        'file' => $context->relativeFile,
        'line' => $context->line,
        'method' => $context->requestMethod,
        'uri' => substr($context->requestUri, 0, 255),
        'user_name' => $userName !== null ? substr($userName, 0, 100) : null,
        'trace' => $context->trace,
        'payload' => $payloadJson,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'incidents' => 1,
    ];
  }

  /**
   * Retorna a instrução SQL de inserção/upsert conforme o driver de banco desejado.
   */
  public function getInsertQuery(?string $driver = null): string
  {
    $targetDriver = $driver ?? (string)$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    return $this->buildInsertQuery($targetDriver);
  }

  private function buildInsertQuery(string $driver): string
  {
    if ($driver === 'sqlite') {
      return "INSERT INTO {$this->tableName}
              (hash, severity, error_class, previous_class, message, file, line, method, uri, user_name, trace, payload, created_at, updated_at, incidents)
              VALUES
              (:hash, :severity, :error_class, :previous_class, :message, :file, :line, :method, :uri, :user_name, :trace, :payload, datetime('now'), datetime('now'), 1)
              ON CONFLICT(hash) DO UPDATE SET updated_at = datetime('now'), incidents = incidents + 1;";
    }

    return "INSERT INTO {$this->tableName}
            (hash, severity, error_class, previous_class, message, file, line, method, uri, user_name, trace, payload, created_at, updated_at, incidents)
            VALUES
            (:hash, :severity, :error_class, :previous_class, :message, :file, :line, :method, :uri, :user_name, :trace, :payload, NOW(), NOW(), 1)
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
              `error_class` VARCHAR(150) NOT NULL,
              `previous_class` VARCHAR(150) NULL,
              `message` TEXT NOT NULL,
              `file` VARCHAR(255) NOT NULL,
              `line` INT NOT NULL,
              `method` VARCHAR(10) NULL,
              `uri` VARCHAR(255) NULL,
              `user_name` VARCHAR(100) NULL,
              `trace` TEXT NOT NULL,
              `payload` TEXT NOT NULL,
              `created_at` DATETIME NOT NULL,
              `updated_at` DATETIME NOT NULL,
              `incidents` INT DEFAULT 1,
              CONSTRAINT `uq_hash` UNIQUE (`hash`)
          );
          CREATE INDEX IF NOT EXISTS `idx_severity` ON `{$this->tableName}` (`severity`);
          CREATE INDEX IF NOT EXISTS `idx_error_class` ON `{$this->tableName}` (`error_class`);
          CREATE INDEX IF NOT EXISTS `idx_updated_at` ON `{$this->tableName}` (`updated_at`);
          CREATE INDEX IF NOT EXISTS `idx_incidents` ON `{$this->tableName}` (`incidents`);";
    } else {
      // Sintaxe compatível com MySQL / MariaDB
      $sql = "CREATE TABLE IF NOT EXISTS `{$this->tableName}` (
              `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
              `hash` VARCHAR(32) NOT NULL,
              `severity` VARCHAR(20) NOT NULL,
              `error_class` VARCHAR(150) NOT NULL,
              `previous_class` VARCHAR(150) NULL,
              `message` TEXT NOT NULL,
              `file` VARCHAR(255) NOT NULL,
              `line` INT NOT NULL,
              `method` VARCHAR(10) NULL,
              `uri` VARCHAR(255) NULL,
              `user_name` VARCHAR(100) NULL,
              `trace` LONGTEXT NOT NULL,
              `payload` JSON NOT NULL,
              `created_at` DATETIME NOT NULL,
              `updated_at` DATETIME NOT NULL,
              `incidents` INT DEFAULT 1,
              UNIQUE KEY `uq_hash` (`hash`),
              INDEX `idx_severity` (`severity`),
              INDEX `idx_error_class` (`error_class`),
              INDEX `idx_updated_at` (`updated_at`),
              INDEX `idx_incidents` (`incidents`)
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
