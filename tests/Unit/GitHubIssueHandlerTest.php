<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Tests\Unit;

use Exception;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Handlers\GitHubIssueHandler;
use HeliomarPM\ErrorTracker\Severity;
use PHPUnit\Framework\TestCase;

class GitHubIssueHandlerTest extends TestCase
{
    public function testRenderTitleDefaultFormat(): void
    {
        $handler = new GitHubIssueHandler('token_mock', 'heliomarpm/error-tracker', 'AppTeste');
        $context = $this->createContext('Erro fatal no banco');

        $title = $handler->renderTitle($context);

        $this->assertStringContainsString('[AppTeste~CRITICAL]', $title);
        $this->assertStringContainsString('Erro fatal no banco', $title);
    }

    public function testRenderBodyDefaultMarkdown(): void
    {
        $handler = new GitHubIssueHandler('token_mock', 'heliomarpm/error-tracker');
        $context = $this->createContext('Falha de timeout');

        $body = $handler->renderBody($context);

        $this->assertStringContainsString('### 🚨 Falha Capturada Automaticamente', $body);
        $this->assertStringContainsString('**Mensagem:** `Falha de timeout`', $body);
        $this->assertStringContainsString('#### 🔍 Stack Trace', $body);
        $this->assertStringContainsString('#### 📦 Payload e Ambiente (Sanitizado)', $body);
        $this->assertStringContainsString($context->hash, $body);
    }

    public function testRenderBodyCustomFormatter(): void
    {
        $handler = new GitHubIssueHandler(
            token: 'token_mock',
            repository: 'heliomarpm/error-tracker',
            bodyFormatter: fn(ErrorContext $ctx) => "CUSTOM: {$ctx->message} ({$ctx->severity->name})"
        );

        $context = $this->createContext('Custom message', Severity::WARNING);
        $body = $handler->renderBody($context);

        $this->assertSame('CUSTOM: Custom message (WARNING)', $body);
    }
    private function createContext(string $message = 'Falha de conexão', Severity $severity = Severity::CRITICAL): ErrorContext
    {
        return new ErrorContext(
            new Exception($message, 500),
            ['token' => '***MASKED***', 'user_id' => 42],
            $severity
        );
    }
}
