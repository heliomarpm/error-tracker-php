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

    public function testResolvesUserFromPortugueseArrayInSession(): void
    {
        $_SESSION = [
            'usuario' => [
                'id' => 42,
                'nome' => 'Heliomar Dev',
                'login' => 'heliomar_dev',
                'email' => 'dev@example.com',
            ],
        ];

        $context = new ErrorContext(new Exception('Erro com sessão em pt-br'), []);
        $this->assertSame('Heliomar Dev', $context->userName);
    }

    public function testResolvesUserFromSessionRootKeys(): void
    {
        $_SESSION = [
            'usuario_nome' => 'Maria Silva',
        ];

        $context = new ErrorContext(new Exception('Erro raiz'), []);
        $this->assertSame('Maria Silva', $context->userName);
    }

    public function testResolvesUserFromSessionObject(): void
    {
        $userObj = new class () {
            public string $nome = 'Carlos Gerente';
        };

        $_SESSION = [
            'usuario' => $userObj,
        ];

        $context = new ErrorContext(new Exception('Erro objeto'), []);
        $this->assertSame('Carlos Gerente', $context->userName);
    }

    public function testResolvesUserFromNestedAuthSession(): void
    {
        $_SESSION = [
            'auth' => [
                'user' => [
                    'name' => 'Admin Autenticado',
                ],
            ],
        ];

        $context = new ErrorContext(new Exception('Erro auth'), []);
        $this->assertSame('Admin Autenticado', $context->userName);
    }

    public function testResolveUserStringReturnsNullForInvalidTypes(): void
    {
        $this->assertNull(ErrorContext::resolveUserString(null));
        $this->assertNull(ErrorContext::resolveUserString('   '));
        $this->assertNull(ErrorContext::resolveUserString([]));
        $this->assertNull(ErrorContext::resolveUserString(new class () {}));
    }
}
