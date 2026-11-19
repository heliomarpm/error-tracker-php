<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Tests\Unit;

use Exception;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Severity;
use PHPUnit\Framework\TestCase;
use Stringable;

final class ErrorContextTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SESSION);
    }

    public function testResolvesUserFromStringable(): void
    {
        $stringableUser = new class () implements Stringable {
            public function __toString(): string
            {
                return 'usuario_stringable';
            }
        };

        $context = new ErrorContext(
            new Exception('Erro teste'),
            [],
            Severity::CRITICAL,
            userName: (string)$stringableUser
        );

        $this->assertSame('usuario_stringable', $context->userName);
    }

    public function testResolvesUserFromDirectParameter(): void
    {
        $context = new ErrorContext(
            new Exception('Erro teste'),
            [],
            Severity::CRITICAL,
            userName: 'usuario_direto'
        );

        $this->assertSame('usuario_direto', $context->userName);
    }

    public function testResolvesUserFromContextArray(): void
    {
        $context = new ErrorContext(
            new Exception('Erro teste context'),
            [
                'context' => [
                    'user' => 'Heliomar Dev',
                ],
            ]
        );

        $this->assertSame('Heliomar Dev', $context->userName);
    }

    public function testResolvesUserFromContextUsernameKey(): void
    {
        $context = new ErrorContext(
            new Exception('Erro teste context'),
            [
                'context' => [
                    'username' => 'heliomar_dev',
                ],
            ]
        );

        $this->assertSame('heliomar_dev', $context->userName);
    }

    public function testDirectParameterOverridesContextUser(): void
    {
        $context = new ErrorContext(
            new Exception('Erro teste'),
            [
                'context' => [
                    'user' => 'user_context',
                ],
            ],
            userName: 'user_direto'
        );

        $this->assertSame('user_direto', $context->userName);
    }

    public function testUserNameIsNullWhenNotProvided(): void
    {
        $context = new ErrorContext(new Exception('Erro sem user'), []);
        $this->assertNull($context->userName);
    }

    public function testCapturesClientIpAndReferer(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.195';
        $_SERVER['HTTP_REFERER'] = 'https://app.example.com/checkout';

        $context = new ErrorContext(new Exception('Erro com IP e Referer'), []);

        $this->assertSame('203.0.113.195', $context->clientIp);
        $this->assertSame('https://app.example.com/checkout', $context->referer);
    }

    public function testExtractsSqlAndParamsFromRequestContext(): void
    {
        $requestData = [
            'context' => [
                'sql' => 'INSERT INTO tb_itens_venda (id_venda, id_prod) VALUES (:venda, :prod)',
                'sql_params' => [
                    'venda' => 104117,
                    'prod' => 3162,
                ],
            ],
        ];

        $context = new ErrorContext(new Exception('Erro SQL'), $requestData);

        $this->assertSame('INSERT INTO tb_itens_venda (id_venda, id_prod) VALUES (:venda, :prod)', $context->sqlQuery);
        $this->assertNotNull($context->sqlParams);
        $this->assertStringContainsString('104117', $context->sqlParams);
    }
}
