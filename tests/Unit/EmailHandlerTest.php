<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Tests\Unit;

use Exception;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Handlers\EmailHandler;
use HeliomarPM\ErrorTracker\Severity;
use HeliomarPM\ErrorTracker\Transports\SmtpTransport;
use PHPUnit\Framework\TestCase;

final class EmailHandlerTest extends TestCase
{
    public function testRenderBodyRendersHtmlByDefault(): void
    {
        $handler = new EmailHandler("support@example.com");
        $context = new ErrorContext(new Exception("Falha crítica de teste"), [], Severity::CRITICAL);

        $body = $handler->renderBody($context);

        $this->assertStringContainsString("Falha crítica de teste", $body);
        $this->assertStringContainsString("<!DOCTYPE html>", $body);
        $this->assertStringContainsString("CRITICAL", $body);
    }

    public function testRenderBodyUsesCustomFormatterWhenProvided(): void
    {
        $handler = new EmailHandler(
            "support@example.com",
            formatter: fn(ErrorContext $ctx) => "CUSTOM EMAIL: " . $ctx->message
        );
        $context = new ErrorContext(new Exception("Erro com formatador customizado"), [], Severity::CRITICAL);

        $body = $handler->renderBody($context);

        $this->assertSame("CUSTOM EMAIL: Erro com formatador customizado", $body);
    }

    public function testReportIgnoresSeveritiesNotInFilter(): void
    {
        $handler = new EmailHandler(
            "support@example.com",
            notifySeverities: [Severity::CRITICAL]
        );

        $context = new ErrorContext(new Exception("Aviso informativo"), [], Severity::INFO);

        // Não deve disparar envio nem estourar erro
        $handler->report($context);
        $this->assertTrue(true);
    }

    public function testReportExecutesWithoutExceptionOnMockSmtp(): void
    {
        $handler = new EmailHandler(
            "support@example.com",
            from: "alerts@example.com",
            notifySeverities: [Severity::CRITICAL],
            smtpTransport: new SmtpTransport("127.0.0.1", 25252, "test_user", "test_pass", "ssl", 1)
        );

        $context = new ErrorContext(
            new Exception("Teste de report com quebras de linha\r\nno assunto"),
            [
                "context" => [
                    "sql" => "SELECT * FROM tb_usuarios WHERE id = :id",
                    "sql_params" => ["id" => 10],
                ],
            ],
            Severity::CRITICAL
        );

        // Deve tentar o envio SMTP, capturar a falha silenciosamente e tentar mail() nativo sem quebrar
        $handler->report($context);
        $this->assertTrue(true);
    }
}
