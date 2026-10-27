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

    public function testIgnoresNonConfiguredSeverity(): void
    {
        $this->expectNotToPerformAssertions();

        $handler = new GitHubIssueHandler(
            token: 'fake_token',
            repository: 'heliomarpm/nonexistent-repo',
            notifySeverities: [Severity::CRITICAL]
        );

        $context = $this->createContext('Warning message', Severity::WARNING);

        // Deve retornar imediatamente sem tentar disparar rede
        $handler->report($context);
    }

    public function testRenderBodyWithCustomTemplate(): void
    {
        $customTemplate = "CUSTOM ISSUE [{{app_name}}] - {{severity}}: {{message}} (Hash: {{hash}})";
        $handler = new GitHubIssueHandler(
            token: 'token_mock',
            repository: 'heliomarpm/error-tracker',
            bodyTemplateOrPath: $customTemplate
        );

        $context = $this->createContext('Falha customizada');
        $body = $handler->renderBody($context);

        $this->assertStringContainsString('CUSTOM ISSUE [ErrorTracker] - CRITICAL: Falha customizada', $body);
        $this->assertStringContainsString('Hash: ' . $context->hash, $body);
    }

    public function testRenderCommentDefaultTemplate(): void
    {
        $handler = new GitHubIssueHandler('token_mock', 'heliomarpm/error-tracker');
        $context = $this->createContext('Reincidência de erro');

        $comment = $handler->renderComment($context);

        $this->assertStringContainsString('🔄 **Reincidência Detectada** em ', $comment);
        $this->assertStringContainsString('- **Severidade:** `CRITICAL`', $comment);
        $this->assertStringContainsString('- **Mensagem:** `Reincidência de erro`', $comment);
        $this->assertStringContainsString('- **Hash ID:** `' . $context->hash . '`', $comment);
    }

    public function testRenderCommentWithCustomTemplate(): void
    {
        $customComment = "REINCIDÊNCIA: {{message}} no arquivo {{file}}:{{line}}";
        $handler = new GitHubIssueHandler(
            token: 'token_mock',
            repository: 'heliomarpm/error-tracker',
            commentTemplateOrPath: $customComment
        );

        $context = $this->createContext('Query timeout');
        $comment = $handler->renderComment($context);

        $this->assertSame("REINCIDÊNCIA: Query timeout no arquivo {$context->file}:{$context->line}", $comment);
    }

    public function testRenderBodyAndCommentWithUserResolution(): void
    {
        $handler = new GitHubIssueHandler(
            token: 'token_mock',
            repository: 'heliomarpm/error-tracker',
            userResolver: fn() => 'admin_resolvido'
        );

        $context = $this->createContext('Erro com usuario');
        $body = $handler->renderBody($context);
        $comment = $handler->renderComment($context);

        $this->assertStringContainsString('**Usuário:** `admin_resolvido`', $body);
        $this->assertStringContainsString('- **Usuário:** `admin_resolvido`', $comment);
    }

    public function testRenderBodyMessageOnly(): void
    {
        $handler = new GitHubIssueHandler('token_mock', 'heliomarpm/error-tracker');
        $context = new ErrorContext(
            new Exception('Apenas aviso informativo'),
            [],
            Severity::INFO,
            isException: false
        );

        $body = $handler->renderBody($context);

        $this->assertStringContainsString('### ℹ️ Evento Informativo Capturado', $body);
        $this->assertStringNotContainsString('### 🚨 Falha Capturada Automaticamente', $body);
        $this->assertStringNotContainsString('#### 🔍 Stack Trace', $body);
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
