<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Tests\Unit;

use ErrorException;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Formatters\TemplateRenderer;
use HeliomarPM\ErrorTracker\Severity;
use PHPUnit\Framework\TestCase;

final class TemplateRendererTest extends TestCase
{
    private TemplateRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new TemplateRenderer();
    }

    public function testRendersStringTemplateWithTags(): void
    {
        $exception = new ErrorException('Erro de sintaxe simulado', 10, E_USER_ERROR, 'src/test.php', 99);
        $context = new ErrorContext($exception, ['user_id' => 77], Severity::WARNING);

        $template = '[{{severity}}] {{message}} in {{file}}:{{line}} (Hash: {{hash}})';
        $rendered = $this->renderer->render($template, $context);

        $this->assertStringContainsString('[WARNING]', $rendered);
        $this->assertStringContainsString('Erro de sintaxe simulado', $rendered);
        $this->assertStringContainsString('src/test.php:99', $rendered);
        $this->assertStringContainsString($context->hash, $rendered);
    }

    public function testRendersFromFile(): void
    {
        $tempFile = sys_get_temp_dir() . '/test_tpl_' . uniqid() . '.tpl';
        file_put_contents($tempFile, 'FILE_TPL: {{severity_value}} - {{message}}');

        $exception = new ErrorException('Mensagem de arquivo', 0, E_USER_NOTICE, 'index.php', 1);
        $context = new ErrorContext($exception, [], Severity::NOTICE);

        $rendered = $this->renderer->render($tempFile, $context);
        @unlink($tempFile);

        $this->assertSame('FILE_TPL: notice - Mensagem de arquivo', $rendered);
    }
}
