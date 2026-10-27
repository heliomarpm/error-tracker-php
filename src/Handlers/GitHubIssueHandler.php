<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Handlers;

use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Formatters\TemplateRenderer;
use HeliomarPM\ErrorTracker\Severity;
use Stringable;
use Throwable;

final class GitHubIssueHandler implements HandlerInterface
{
  private TemplateRenderer $renderer;
  private string $bodyTemplatePath;
  private string $commentTemplatePath;

  /** @var (callable(ErrorContext): string)|null */
  private $bodyFormatter = null;

  /**
   * @param string $token Token de Acesso Pessoal (PAT) do GitHub.
   * @param string $repository Repositório no formato "usuario/repo" (ex: "heliomarpm/error-tracker").
   * @param string $application Nome da aplicação.
   * @param array<Severity>|null $notifySeverities Níveis de severidade que disparam issue (null notifica todos).
   * @param int $timeoutSeconds Timeout da requisição cURL em segundos.
   * @param bool $deduplicateOpenIssues Se true, busca issue aberta com o mesmo hash e adiciona comentário em vez de duplicar.
   * @param (callable(ErrorContext): string)|null $bodyFormatter Formatador customizado para o corpo da issue em Markdown.
   * @param string|null $userName Nome ou identificador estático do usuário padrão para este handler.
   * @param (callable(): (string|Stringable|null))|null $userResolver Callback dinâmico para resolução do usuário neste handler.
   * @param string|null $bodyTemplateOrPath Caminho do arquivo ou string de template customizado para o corpo da issue.
   * @param string|null $commentTemplateOrPath Caminho do arquivo ou string de template customizado para comentários de reincidência.
   */
  public function __construct(
      private readonly string $token,
      private readonly string $repository,
      private readonly string $application = 'ErrorTracker',
      private readonly ?array $notifySeverities = [Severity::CRITICAL, Severity::WARNING],
      private readonly int $timeoutSeconds = 3,
      private readonly bool $deduplicateOpenIssues = true,
      ?callable $bodyFormatter = null,
      private readonly ?string $userName = null,
      private $userResolver = null,
      ?string $bodyTemplateOrPath = null,
      ?string $commentTemplateOrPath = null
  ) {
    $this->bodyFormatter = $bodyFormatter;
    $this->renderer = new TemplateRenderer();
    $this->bodyTemplatePath = $bodyTemplateOrPath ?? dirname(__DIR__) . '/Templates/github_issue_body.tpl';
    $this->commentTemplatePath = $commentTemplateOrPath ?? dirname(__DIR__) . '/Templates/github_issue_comment.tpl';
  }

  public function renderTitle(ErrorContext $context): string
  {
    $appName = !empty($context->appName) && $context->appName !== 'ErrorTracker'
        ? $context->appName
        : $this->application;

    $title = "[{$appName}~{$context->severity->name}] " . substr($context->message, 0, 80);
    if (strlen($context->message) > 80) {
      $title .= '...';
    }
    return $title;
  }

  public function renderBody(ErrorContext $context): string
  {
    if ($this->bodyFormatter !== null) {
      return ($this->bodyFormatter)($context);
    }

    $extraTags = $this->resolveExtraTags($context);

    return $this->renderer->render($this->bodyTemplatePath, $context, $extraTags);
  }

  public function renderComment(ErrorContext $context): string
  {
    $extraTags = $this->resolveExtraTags($context);

    return $this->renderer->render($this->commentTemplatePath, $context, $extraTags);
  }

  public function report(ErrorContext $context): void
  {
    if ($this->notifySeverities !== null && !in_array($context->severity, $this->notifySeverities, true)) {
      return;
    }

    $url = "https://api.github.com/repos/{$this->repository}/issues";

    $title = $this->renderTitle($context);
    $body = $this->renderBody($context);
    $labels = ['bug', 'automated-report', 'severity:' . strtolower($context->severity->value)];

    // Se a deduplicação de issues abertas estiver habilitada, verifica se já existe issue aberta com o mesmo hash
    if ($this->deduplicateOpenIssues) {
      $existingIssueNumber = $this->findOpenIssueNumber($context->hash);
      if ($existingIssueNumber !== null) {
        $commentUrl = "https://api.github.com/repos/{$this->repository}/issues/{$existingIssueNumber}/comments";
        $commentPayload = json_encode([
            'body' => $this->renderComment($context),
        ]);
        $this->dispatch($commentUrl, $commentPayload ?: '{}');
        return;
      }
    }

    $payload = json_encode([
        'title' => $title,
        'body' => $body,
        'labels' => $labels,
    ]);

    // Executa a chamada via cURL
    $this->dispatch($url, $payload ?: '{}');
  }

  /**
   * Resolve tags extras como usuário dinâmico e aplicação para interpolação nos templates.
   *
   * @return array<string, string|int|float|null>
   */
  private function resolveExtraTags(ErrorContext $context): array
  {
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
          // Absorve exceções
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

    $appName = !empty($context->appName) && $context->appName !== 'ErrorTracker'
        ? $context->appName
        : $this->application;

    $extraTags['app_name'] = $appName;
    $extraTags['application'] = $appName;

    return $extraTags;
  }

  private function findOpenIssueNumber(string $hash): ?int
  {
    $searchUrl = "https://api.github.com/search/issues?q=" . urlencode("repo:{$this->repository} is:issue is:open {$hash}");

    $ch = curl_init($searchUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Accept: application/vnd.github.v3+json",
            "User-Agent: {$this->application}",
            "Authorization: Bearer {$this->token}",
        ],
        CURLOPT_TIMEOUT => $this->timeoutSeconds,
        CURLOPT_CONNECTTIMEOUT => min(2, $this->timeoutSeconds),
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && is_string($response)) {
      $data = json_decode($response, true);
      if (is_array($data) && !empty($data['items']) && is_array($data['items'][0]) && isset($data['items'][0]['number'])) {
        return (int)$data['items'][0]['number'];
      }
    }

    return null;
  }

  private function dispatch(string $url, string $payload): void
  {
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            "Accept: application/vnd.github.v3+json",
            "Content-Type: application/json",
            "User-Agent: {$this->application}",
            "Authorization: Bearer {$this->token}",
        ],
        CURLOPT_TIMEOUT => $this->timeoutSeconds,
        CURLOPT_CONNECTTIMEOUT => min(2, $this->timeoutSeconds),
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($httpCode !== 201) {
      error_log("HeliomarPM ErrorTracker falhou ao comunicar com a API do GitHub. HTTP Code: {$httpCode}. Resposta: {$response}");
    }
  }
}
