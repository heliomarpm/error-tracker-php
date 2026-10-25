<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Handlers;

use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Severity;

class WebhookHandler implements HandlerInterface
{
  /** @var (callable(ErrorContext): array<string, mixed>)|null */
  private $payloadFormatter = null;

  /**
   * @param string $webhookUrl URL do webhook (Slack, Discord, Teams ou customizado).
   * @param array<Severity> $notifySeverities Níveis de severidade que disparam a notificação.
   * @param string $format Formato do payload: 'slack', 'discord' ou 'generic'.
   * @param int $timeoutSeconds Timeout da requisição em segundos.
   * @param (callable(ErrorContext): array<string, mixed>)|null $payloadFormatter Callback customizado de formatação.
   */
  public function __construct(
      private readonly string $webhookUrl,
      private readonly array $notifySeverities = [Severity::CRITICAL],
      private readonly string $format = 'generic',
      private readonly int $timeoutSeconds = 3,
      ?callable $payloadFormatter = null
  ) {
    $this->payloadFormatter = $payloadFormatter;
  }

  public function report(ErrorContext $context): void
  {
    if (!in_array($context->severity, $this->notifySeverities, true)) {
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

    return match (strtolower($this->format)) {
      'slack' => [
          'text' => "🚨 *[{$context->severity->name}] Incidente Detectado*",
          'attachments' => [
              [
                  'color' => $context->severity === Severity::CRITICAL ? '#d9534f' : '#f0ad4e',
                  'title' => $context->message,
                  'fields' => [
                      ['title' => 'Arquivo', 'value' => "{$context->file}:{$context->line}", 'short' => true],
                      ['title' => 'Hash ID', 'value' => $context->hash, 'short' => true],
                  ],
                  'footer' => 'HeliomarPM ErrorTracker',
                  'ts' => time(),
              ],
          ],
      ],
      'discord' => [
          'content' => "🚨 **[{$context->severity->name}] Incidente Detectado**",
          'embeds' => [
              [
                  'title' => substr($context->message, 0, 250),
                  'description' => "```php\n" . substr($context->trace, 0, 1000) . "\n```",
                  'color' => $context->severity === Severity::CRITICAL ? 14242639 : 15773006,
                  'fields' => [
                      ['name' => 'Arquivo', 'value' => "{$context->file}:{$context->line}", 'inline' => true],
                      ['name' => 'Hash ID', 'value' => "`{$context->hash}`", 'inline' => true],
                  ],
                  'footer' => ['text' => 'HeliomarPM ErrorTracker'],
                  'timestamp' => date('c'),
              ],
          ],
      ],
      default => [
          'hash' => $context->hash,
          'severity' => $context->severity->value,
          'message' => $context->message,
          'file' => $context->file,
          'line' => $context->line,
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
