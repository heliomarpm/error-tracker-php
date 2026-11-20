<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Handlers;

use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Formatters\TemplateRenderer;
use HeliomarPM\ErrorTracker\Severity;

abstract class AbstractHandler implements HandlerInterface
{
  private static ?TemplateRenderer $sharedRenderer = null;

  /**
   * @param array<Severity>|null $notifySeverities
   */
  public function __construct(
      protected readonly ?array $notifySeverities = null
  ) {}

  /**
   * Verifica se o incidente atual atende ao filtro de severidade configurado no handler.
   */
  protected function shouldReport(ErrorContext $context): bool
  {
    return $this->notifySeverities === null
        || in_array($context->severity, $this->notifySeverities, true);
  }

  /**
   * Retorna a instância compartilhada do motor de templates.
   */
  protected function renderer(): TemplateRenderer
  {
    return self::$sharedRenderer ??= new TemplateRenderer();
  }
}
