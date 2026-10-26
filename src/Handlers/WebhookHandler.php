<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Handlers;

use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Severity;
use Stringable;
use Throwable;

class WebhookHandler implements HandlerInterface
{
  /** @var (callable(ErrorContext): array<string, mixed>)|null */
  private $payloadFormatter = null;

  /**
   * @param string $webhookUrl URL do webhook (Slack, Discord, Teams ou customizado).
   * @param array<Severity>|null $notifySeverities Níveis de severidade que disparam a notificação (null notifica todos).
   * @param string $format Formato do payload: 'slack', 'discord' ou 'generic'.
   * @param int $timeoutSeconds Timeout da requisição em segundos.
   * @param (callable(ErrorContext): array<string, mixed>)|null $payloadFormatter Callback customizado de formatação.
   * @param string|null $userName Nome ou identificador estático do usuário padrão para este handler.
   * @param (callable(): (string|Stringable|null))|null $userResolver Callback dinâmico para resolução do usuário neste handler.
   */
  public function __construct(
      private readonly string $webhookUrl,
      private readonly ?array $notifySeverities = [Severity::CRITICAL],
      private readonly string $format = 'generic',
      private readonly int $timeoutSeconds = 3,
      ?callable $payloadFormatter = null,
      private readonly ?string $userName = null,
      private $userResolver = null
  ) {
    $this->payloadFormatter = $payloadFormatter;
  }

  public function report(ErrorContext $context): void
  {
    if ($this->notifySeverities !== null && !in_array($context->severity, $this->notifySeverities, true)) {
      return;
    }

    $payload = $this->buildPayload($context);
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($json === false) {
      return;
    }

    $this->dispatch($json);
  }

  /**
   * Constrói a estrutura do payload (público para permitir pré-visualização no Demo).
   *
   * @return array<string, mixed>
   */
  public function buildPayload(ErrorContext $context): array
  {
    if ($this->payloadFormatter !== null) {
      return ($this->payloadFormatter)($context);
    }

    $userName = $context->userName;
    if ($userName === null) {
      if ($this->userResolver !== null) {
        try {
          $resolved = ($this->userResolver)();
          if ($resolved !== null && $resolved !== '') {
            $userName = (string)$resolved;
          }
        } catch (Throwable) {
          // Absorve exceções
        }
      }
      if ($userName === null && $this->userName !== null) {
        $userName = $this->userName;
      }
    }

    $appLabel = $context->appName . ($context->environment !== 'production' ? " [{$context->environment}]" : '');

    $slackFields = [
        ['title' => 'Aplicação', 'value' => $appLabel, 'short' => true],
        ['title' => 'Arquivo', 'value' => "{$context->file}:{$context->line}", 'short' => true],
        ['title' => 'Hash ID', 'value' => $context->hash, 'short' => true],
    ];
    if ($userName !== null) {
        $slackFields[] = ['title' => 'Usuário', 'value' => $userName, 'short' => true];
    }

    $discordFields = [
        ['name' => 'Aplicação', 'value' => $appLabel, 'inline' => true],
        ['name' => 'Arquivo', 'value' => "{$context->file}:{$context->line}", 'inline' => true],
        ['name' => 'Hash ID', 'value' => "`{$context->hash}`", 'inline' => true],
    ];
    if ($userName !== null) {
        $discordFields[] = ['name' => 'Usuário', 'value' => $userName, 'inline' => true];
    }

    return match (strtolower($this->format)) {
      'slack' => [
          'text' => ($context->isException ? '🚨' : 'ℹ️') . " *[{$context->severity->name}] " . ($context->isException ? 'Incidente Detectado' : 'Registro de Evento') . '*',
          'attachments' => [
              [
                  'color' => match ($context->severity) {
                      Severity::CRITICAL => '#d9534f',
                      Severity::WARNING => '#f0ad4e',
                      Severity::INFO => '#3498db',
                      default => '#777777',
                  },
                  'title' => $context->message,
                  'fields' => $slackFields,
                  'footer' => "HeliomarPM ErrorTracker | {$context->appName}",
                  'ts' => time(),
              ],
          ],
      ],
      'discord' => [
          'content' => ($context->isException ? '🚨' : 'ℹ️') . " **[{$context->severity->name}] " . ($context->isException ? 'Incidente Detectado' : 'Registro de Evento') . '**',
          'embeds' => [
              [
                  'title' => substr($context->message, 0, 250),
                  'description' => !empty($context->trace) ? "```php\n" . substr($context->trace, 0, 1000) . "\n```" : null,
                  'color' => match ($context->severity) {
                      Severity::CRITICAL => 14242639,
                      Severity::WARNING => 15773006,
                      Severity::INFO => 3447003,
                      default => 7829367,
                  },
                  'fields' => $discordFields,
                  'footer' => ['text' => "HeliomarPM ErrorTracker | {$context->appName}"],
                  'timestamp' => date('c'),
              ],
          ],
      ],
      default => [
          'hash' => $context->hash,
          'app_name' => $context->appName,
          'environment' => $context->environment,
          'severity' => $context->severity->value,
          'message' => $context->message,
          'file' => $context->file,
          'line' => $context->line,
          'user_name' => $userName,
          'trace' => $context->trace,
          'payload' => $context->requestData,
          'timestamp' => date('Y-m-d H:i:s'),
      ],
    };
  }

  private function dispatch(string $jsonPayload): void
  {
    $ch = curl_init($this->webhookUrl);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $jsonPayload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: HeliomarPM-ErrorTracker',
        ],
        CURLOPT_TIMEOUT => $this->timeoutSeconds,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400 || $response === false) {
      error_log("HeliomarPM ErrorTracker WebhookHandler falhou com HTTP {$httpCode} para {$this->webhookUrl}");
    }
  }
}
