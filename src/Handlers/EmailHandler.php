<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Handlers;

use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Formatters\TemplateRenderer;
use HeliomarPM\ErrorTracker\Handlers\Traits\ResolvesUserTrait;
use HeliomarPM\ErrorTracker\Severity;
use Stringable;

final class EmailHandler implements HandlerInterface
{
  use ResolvesUserTrait;
  private TemplateRenderer $renderer;
  private string $templatePath;

  /** @var (callable(ErrorContext): string)|null */
  private $formatter = null;

  /**
   * @param string $to O e-mail da equipe de suporte (ex: email.tech@gmail.com).
   * @param string $from O remetente do e-mail (ex: noreply@errortracker.com.br).
   * @param array<Severity>|null $notifySeverities Array com os níveis de erro que disparam e-mail (null notifica todos).
   * @param string $format Formato de envio: 'html' ou 'text'.
   * @param string|null $templateOrPath Caminho do arquivo de template ou string customizada.
   * @param string $subjectTemplate Template do assunto com tags (ex: 🚨 [{{severity}}] {{message}}).
   * @param (callable(ErrorContext): string)|null $formatter Callback customizado para formatação do corpo.
   * @param string|null $userName Nome ou identificador estático do usuário padrão para este handler.
   * @param (callable(): (string|Stringable|null))|null $userResolver Callback dinâmico para resolução do usuário neste handler.
   */
  public function __construct(
      private readonly string $to,
      private readonly string $from = 'noreply@errortracker.com.br',
      private readonly ?array $notifySeverities = [Severity::CRITICAL],
      private readonly string $format = 'html',
      ?string $templateOrPath = null,
      private readonly string $subjectTemplate = '<!-- IF_EXCEPTION -->🚨 [{{severity}}] Incidente Detectado: {{message}}<!-- /IF_EXCEPTION --><!-- IF_MESSAGE_ONLY -->ℹ️ [{{severity}}] Evento: {{message}}<!-- /IF_MESSAGE_ONLY -->',
      ?callable $formatter = null,
      private readonly ?string $userName = null,
      private $userResolver = null
  ) {
    $this->formatter = $formatter;
    $this->renderer = new TemplateRenderer();

    $defaultTemplate = strtolower($this->format) === 'text'
      ? dirname(__DIR__) . '/Templates/email_text.tpl'
      : dirname(__DIR__) . '/Templates/email_html.tpl';

    $this->templatePath = $templateOrPath ?? $defaultTemplate;
  }

  public function report(ErrorContext $context): void
  {
    // 1. Filtro de Severidade
    if ($this->notifySeverities !== null && !in_array($context->severity, $this->notifySeverities, true)) {
      return;
    }

    $subject = $this->renderer->render($this->subjectTemplate, $context);
    $body = $this->renderBody($context);

    // 2. Cabeçalhos conforme o formato selecionado
    $contentType = strtolower($this->format) === 'text'
      ? 'text/plain; charset=utf-8'
      : 'text/html; charset=utf-8';

    $headers = [
        'MIME-Version: 1.0',
        "Content-type: {$contentType}",
        'From: Error Tracker <' . $this->from . '>',
        'Reply-To: ' . $this->from,
        'X-Mailer: PHP/' . phpversion(),
    ];

    // 3. Disparo do E-mail
    $success = @mail($this->to, $subject, $body, implode("\r\n", $headers));

    if (!$success) {
      error_log("HeliomarPM ErrorTracker falhou ao enviar e-mail de alerta crítico para {$this->to}");
    }
  }

  /**
   * Renderiza o corpo da mensagem (público para permitir pré-visualização no Demo).
   */
  public function renderBody(ErrorContext $context): string
  {
    if ($this->formatter !== null) {
      return ($this->formatter)($context);
    }

    $extraTags = [];
    $userName = $this->resolveUser($context);
    if ($userName !== null) {
      $extraTags['usuario_nome'] = $userName;
      $extraTags['user'] = $userName;
      $extraTags['user_name'] = $userName;
    }

    return $this->renderer->render($this->templatePath, $context, $extraTags);
  }
}
