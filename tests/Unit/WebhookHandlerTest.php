<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Tests\Unit;

use Exception;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Handlers\WebhookHandler;
use HeliomarPM\ErrorTracker\Severity;
use PHPUnit\Framework\TestCase;

class WebhookHandlerTest extends TestCase
{
    public function testBuildSlackPayload(): void
    {
        $handler = new WebhookHandler('https://hooks.slack.com/services/mock', format: 'slack');
        $context = $this->createContext('Timeout no gateway');

        $payload = $handler->buildPayload($context);

        $this->assertArrayHasKey('text', $payload);
        $this->assertStringContainsString('[CRITICAL] Incidente Detectado', $payload['text']);
        $this->assertArrayHasKey('attachments', $payload);
        $this->assertSame('#d9534f', $payload['attachments'][0]['color']);
        $this->assertSame('Timeout no gateway', $payload['attachments'][0]['title']);
    }

    public function testBuildDiscordPayload(): void
    {
        $handler = new WebhookHandler('https://discord.com/api/webhooks/mock', format: 'discord');
        $context = $this->createContext('Erro no pedido');

        $payload = $handler->buildPayload($context);

        $this->assertArrayHasKey('content', $payload);
        $this->assertStringContainsString('**[CRITICAL] Incidente Detectado**', $payload['content']);
        $this->assertArrayHasKey('embeds', $payload);
        $this->assertSame('Erro no pedido', $payload['embeds'][0]['title']);
    }

    public function testBuildGenericJsonPayload(): void
    {
        $handler = new WebhookHandler('https://api.empresa.com/webhook', format: 'generic');
        $context = $this->createContext('Alerta genérico', Severity::INFO);

        $payload = $handler->buildPayload($context);

        $this->assertSame('info', $payload['severity']);
        $this->assertSame('Alerta genérico', $payload['message']);
        $this->assertSame($context->hash, $payload['hash']);
    }

    public function testIgnoresNonConfiguredSeverity(): void
    {
        $this->expectNotToPerformAssertions();

        // Webhook com URL inválida que quebraria se tentasse enviar cURL
        $handler = new WebhookHandler('http://invalid.local', notifySeverities: [Severity::CRITICAL]);
        $context = $this->createContext('Evento leve de info', Severity::INFO);

        // Não deve tentar disparar cURL nem lançar exceção
        $handler->report($context);
    }

    public function testResolvesUserFallbackInWebhookPayload(): void
    {
        $handler = new WebhookHandler(
            'https://hooks.slack.com/services/mock',
            format: 'slack',
            userResolver: fn() => 'usuario_webhook_dyn'
        );
        $context = $this->createContext('Erro com usuario fallback');
        $payload = $handler->buildPayload($context);

        $fields = $payload['attachments'][0]['fields'] ?? [];
        $userField = null;
        foreach ($fields as $field) {
            if (($field['title'] ?? '') === 'Usuário') {
                $userField = $field;
                break;
            }
        }

        $this->assertNotNull($userField);
        $this->assertSame('usuario_webhook_dyn', $userField['value']);
    }

    private function createContext(string $message = 'Falha no pagamento', Severity $severity = Severity::CRITICAL): ErrorContext
    {
        return new ErrorContext(
            new Exception($message, 503),
            ['order_id' => 'PED-1234'],
            $severity
        );
    }
}
