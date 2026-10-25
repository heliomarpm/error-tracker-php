<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Tests\Unit;

use ErrorException;
use Exception;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Formatters\TemplateRenderer;
use HeliomarPM\ErrorTracker\Severity;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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

    public function testRendersConditionalsWhenTruthyAndFalsy(): void
    {
        $template = "Início\n<!-- IF_PREVIOUS -->\nCausa: {{previous_class}}: {{previous_message}}\n<!-- /IF_PREVIOUS -->\nFim";

        // Caso Falsy: sem exceção anterior
        $excWithoutPrev = new ErrorException('Erro simples', 0);
        $ctxWithoutPrev = new ErrorContext($excWithoutPrev, []);
        $renderedFalsy = $this->renderer->render($template, $ctxWithoutPrev);

        $this->assertStringNotContainsString('Causa:', $renderedFalsy);
        $this->assertSame("Início\nFim", trim($renderedFalsy));

        // Caso Truthy: com exceção encadeada
        $root = new RuntimeException('Falha no banco');
        $excWithPrev = new Exception('Erro no serviço', 500, $root);
        $ctxWithPrev = new ErrorContext($excWithPrev, []);
        $renderedTruthy = $this->renderer->render($template, $ctxWithPrev);

        $this->assertStringContainsString('Causa: RuntimeException: Falha no banco', $renderedTruthy);
    }

    public function testRendersDetailedDiagnosticsTags(): void
    {
        $root = new RuntimeException('Exceção raiz');
        $exception = new InvalidArgumentException('Argumento inválido', 400, $root);
        $context = new ErrorContext(
            $exception,
            ['context' => ['user' => 'Maria Silva']],
            Severity::CRITICAL
        );

        $template = 'Tipo: {{error_title}} | Msg: {{error_message}} | Causa: {{previous_class}} | User: {{usuario_nome}} | PHP: {{php_version}} | Mem: {{memory_usage}}';
        $rendered = $this->renderer->render($template, $context);

        $this->assertStringContainsString('Tipo: InvalidArgumentException', $rendered);
        $this->assertStringContainsString('Msg: Argumento inválido', $rendered);
        $this->assertStringContainsString('Causa: RuntimeException', $rendered);
        $this->assertStringContainsString('User: Maria Silva', $rendered);
        $this->assertStringContainsString('PHP: ' . PHP_VERSION, $rendered);
        $this->assertStringContainsString('Mem: ' . $context->memoryUsage, $rendered);
    }

    public function testRendersBuiltinEmailTemplatesWithoutErrors(): void
    {
        $root = new RuntimeException('Conexão recusada');
        $exception = new Exception('Falha no checkout', 500, $root);
        $context = new ErrorContext(
            $exception,
            ['post' => ['item' => 'Livro'], 'context' => ['user' => 'Carlos Dev']],
            Severity::CRITICAL
        );

        $textTplPath = __DIR__ . '/../../src/Templates/email_text.tpl';
        $htmlTplPath = __DIR__ . '/../../src/Templates/email_html.tpl';

        $renderedText = $this->renderer->render($textTplPath, $context);
        $renderedHtml = $this->renderer->render($htmlTplPath, $context);

        // Valida texto
        $this->assertStringContainsString('🚨 [CRITICAL] ALERTA DE INCIDENTE', $renderedText);
        $this->assertStringContainsString('Tipo:         Exception', $renderedText);
        $this->assertStringContainsString('Causa Raiz:   RuntimeException: Conexão recusada', $renderedText);
        $this->assertStringContainsString('Usuário:      Carlos Dev', $renderedText);

        // Valida HTML
        $this->assertStringContainsString('🚨 CRITICAL', $renderedHtml);
        $this->assertStringContainsString('Exception', $renderedHtml);
        $this->assertStringContainsString('RuntimeException:</strong> Conexão recusada', $renderedHtml);
        $this->assertStringContainsString('Carlos Dev', $renderedHtml);
    }
}
