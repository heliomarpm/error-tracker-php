<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Handlers;

use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Formatters\TemplateRenderer;
use HeliomarPM\ErrorTracker\Severity;
use Stringable;
use Throwable;

final class FilelogHandler implements HandlerInterface
{
  private string $logDirectory;
  private string $templatePath;
  private TemplateRenderer $renderer;

  /** @var (callable(ErrorContext): string)|null */
  private $formatter = null;

  /**
   * @param string $logDirectory O diretório onde serão armazenados os logs.
   * @param string|null $templateOrPath Caminho do arquivo de template ou string customizada.
   * @param array<Severity>|null $notifySeverities Níveis de severidade que gravam log (null grava todas).
   * @param (callable(ErrorContext): string)|null $formatter Callback customizado para formatação.
   * @param string|null $userName Nome ou identificador estático do usuário padrão para este handler.
   * @param (callable(): (string|Stringable|null))|null $userResolver Callback dinâmico para resolução do usuário neste handler.
   */
  public function __construct(
      string $logDirectory,
      ?string $templateOrPath = null,
      private readonly ?array $notifySeverities = null,
      ?callable $formatter = null,
      private readonly ?string $userName = null,
      private $userResolver = null
  ) {
    $this->logDirectory = rtrim($logDirectory, '/\\');
    $this->templatePath = $templateOrPath ?? dirname(__DIR__) . '/Templates/filelog.tpl';
    $this->formatter = $formatter;
    $this->renderer = new TemplateRenderer();

    // Garante que o diretório de logs exista
    if (!is_dir($this->logDirectory)) {
      mkdir($this->logDirectory, 0777, true);
    }
  }

  public function report(ErrorContext $context): void
  {
    if ($this->notifySeverities !== null && !in_array($context->severity, $this->notifySeverities, true)) {
      return;
    }

    $logEntry = $this->renderEntry($context);

    // Nome do arquivo rotacionado por dia e severidade
    $file = $this->getLogFilename($context);

    // Salva preservando o UTF-8 natural do PHP com bloqueio exclusivo
    file_put_contents($file, $logEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
  }

  /**
   * Renderiza a entrada de log (público para permitir pré-visualização no Demo).
   */
  public function renderEntry(ErrorContext $context): string
  {
    if ($this->formatter !== null) {
      return ($this->formatter)($context);
    }

    $extraTags = [];
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
      if ($userName !== null) {
        $extraTags['usuario_nome'] = $userName;
        $extraTags['user'] = $userName;
        $extraTags['user_name'] = $userName;
      }
    }

    return $this->renderer->render($this->templatePath, $context, $extraTags);
  }

  /**
   * Retorna o caminho do arquivo de log rotacionado para a severidade e data.
   */
  public function getLogFilename(ErrorContext $context, ?string $date = null): string
  {
    $dateStr = $date ?? date('Ymd');
    return "{$this->logDirectory}/{$context->severity->name}_{$dateStr}.log";
  }
}
