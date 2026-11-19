<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Handlers;

use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Formatters\TemplateRenderer;
use HeliomarPM\ErrorTracker\Severity;
use HeliomarPM\ErrorTracker\Transports\SmtpTransport;
use Throwable;

final class EmailHandler implements HandlerInterface
{
  private TemplateRenderer $renderer;
  private string $templatePath;
  private ?SmtpTransport $smtpTransport = null;

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
   * @param string|null $smtpHost Host do servidor SMTP (ex: smtp.titan.email).
   * @param int $smtpPort Porta do servidor SMTP (465 para SSL, 587 para TLS/STARTTLS).
   * @param string|null $smtpUser Usuário de autenticação SMTP.
   * @param string|null $smtpPass Senha de autenticação SMTP.
   * @param string $smtpSecure Tipo de criptografia: 'ssl', 'tls' ou 'none'.
   * @param int $smtpTimeout Timeout da conexão SMTP em segundos.
   * @param bool $multipart Se verdadeiro e formato for HTML, envia e-mail multipart/alternative com versão texto e HTML.
   */
  public function __construct(
      private readonly string $to,
      private readonly string $from = 'noreply@errortracker.com.br',
      private readonly ?array $notifySeverities = [Severity::CRITICAL],
      private readonly string $format = 'html',
      ?string $templateOrPath = null,
      private readonly string $subjectTemplate = '<!-- IF_EXCEPTION -->🚨 [{{severity}}] Incidente Detectado: {{message}}<!-- /IF_EXCEPTION --><!-- IF_MESSAGE_ONLY -->ℹ️ [{{severity}}] Evento: {{message}}<!-- /IF_MESSAGE_ONLY -->',
      ?callable $formatter = null,
      private readonly ?string $smtpHost = null,
      private readonly int $smtpPort = 465,
      private readonly ?string $smtpUser = null,
      private readonly ?string $smtpPass = null,
      private readonly string $smtpSecure = 'ssl',
      private readonly int $smtpTimeout = 10,
      private readonly bool $multipart = true
  ) {
    $this->formatter = $formatter;
    $this->renderer = new TemplateRenderer();

    $defaultTemplate = strtolower($this->format) === 'text'
      ? dirname(__DIR__) . '/Templates/email_text.tpl'
      : dirname(__DIR__) . '/Templates/email_html.tpl';

    $this->templatePath = $templateOrPath ?? $defaultTemplate;

    // Pré-instancia o transporte SMTP se configurado, evitando recriação a cada report()
    if (!empty($this->smtpHost) && !empty($this->smtpUser)) {
      $this->smtpTransport = new SmtpTransport(
          $this->smtpHost,
          $this->smtpPort,
          $this->smtpUser,
          $this->smtpPass ?? '',
          $this->smtpSecure,
          $this->smtpTimeout
      );
    }
  }

  public function report(ErrorContext $context): void
  {
    // 1. Filtro de Severidade
    if ($this->notifySeverities !== null && !in_array($context->severity, $this->notifySeverities, true)) {
      return;
    }

    // 2. Renderização e Sanitização do Assunto (evita header injection / quebras \r\n no mail)
    $renderedSubject = $this->renderer->render($this->subjectTemplate, $context);
    $cleanSubject = trim(preg_replace('/[\r\n\t]+/', ' ', $renderedSubject) ?? '');
    $encodedSubject = mb_encode_mimeheader($cleanSubject, 'UTF-8', 'B', '');

    $body = $this->renderBody($context);

    // 3. Montagem do corpo e cabeçalhos
    $isHtml = strtolower($this->format) === 'html';

    if ($isHtml && $this->multipart && $this->formatter === null) {
      $textTemplate = dirname(__DIR__) . '/Templates/email_text.tpl';
      $textBody = $this->renderBodyWithTemplate($context, $textTemplate);
      $boundary = "==Multipart_Boundary_x" . md5(uniqid((string)mt_rand(), true)) . "x";

      $headers = [
          'MIME-Version: 1.0',
          "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
          'From: Error Tracker <' . $this->from . '>',
          'Reply-To: ' . $this->from,
          'X-Mailer: PHP/' . phpversion(),
      ];

      $fullBody = "--{$boundary}\r\n"
          . "Content-Type: text/plain; charset=UTF-8\r\n"
          . "Content-Transfer-Encoding: 8bit\r\n\r\n"
          . $textBody . "\r\n\r\n"
          . "--{$boundary}\r\n"
          . "Content-Type: text/html; charset=UTF-8\r\n"
          . "Content-Transfer-Encoding: 8bit\r\n\r\n"
          . $body . "\r\n\r\n"
          . "--{$boundary}--";
    } else {
      $contentType = $isHtml ? 'text/html; charset=utf-8' : 'text/plain; charset=utf-8';
      $headers = [
          'MIME-Version: 1.0',
          "Content-Type: {$contentType}",
          'From: Error Tracker <' . $this->from . '>',
          'Reply-To: ' . $this->from,
          'X-Mailer: PHP/' . phpversion(),
      ];
      $fullBody = $body;
    }

    // 4. Disparo via SMTP Autenticado (se configurado)
    $sent = false;
    if ($this->smtpTransport !== null) {
      try {
        $sent = $this->smtpTransport->send($this->from, $this->to, $encodedSubject, $fullBody, $headers);
        if (!$sent) {
          error_log("HeliomarPM ErrorTracker falha no transporte SMTP ({$this->smtpHost}): " . $this->smtpTransport->getLastError());
        }
      } catch (Throwable $e) {
        error_log("HeliomarPM ErrorTracker exceção no transporte SMTP: " . $e->getMessage());
      }
    }

    // 5. Fallback para mail() nativo com envelope sender (-f)
    if (!$sent) {
      $additionalParams = filter_var($this->from, FILTER_VALIDATE_EMAIL) ? "-f" . $this->from : "";
      if ($additionalParams !== "") {
        $sent = (bool)@mail($this->to, $encodedSubject, $fullBody, implode("\r\n", $headers), $additionalParams);
      }
      if (!$sent) {
        $sent = (bool)@mail($this->to, $encodedSubject, $fullBody, implode("\r\n", $headers));
      }
    }

    if (!$sent) {
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

    return $this->renderBodyWithTemplate($context, $this->templatePath);
  }

  private function renderBodyWithTemplate(ErrorContext $context, string $templatePath): string
  {
    return $this->renderer->render($templatePath, $context);
  }
}
