<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Tests\Unit;

use HeliomarPM\ErrorTracker\DataSanitizer;
use PHPUnit\Framework\TestCase;

final class DataSanitizerTest extends TestCase
{
    private DataSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new DataSanitizer();
    }

    public function testMasksBlacklistedKeysCompletely(): void
    {
        $input = [
            'password' => 'super-secret-123',
            'senha' => 'minhaSenha',
            'token' => 'eyJhbGciOi...',
            'authorization' => 'Bearer 123456',
            'secret' => 'api-secret-key',
            'cvv' => '999',
            'user' => 'heliomar',
        ];

        $clean = $this->sanitizer->sanitize($input);

        $this->assertSame('********', $clean['password']);
        $this->assertSame('********', $clean['senha']);
        $this->assertSame('********', $clean['token']);
        $this->assertSame('********', $clean['authorization']);
        $this->assertSame('********', $clean['secret']);
        $this->assertSame('********', $clean['cvv']);
        $this->assertSame('heliomar', $clean['user']);
    }

    public function testMasksEmailAddressPartially(): void
    {
        $input = [
            'email' => 'heliomarpm@gmail.com',
            'short' => 'ab@test.com',
        ];

        $clean = $this->sanitizer->sanitize($input);

        $this->assertSame('hel*******@gmail.com', $clean['email']);
        $this->assertSame('a*@test.com', $clean['short']);
    }

    public function testMasksCpfPartially(): void
    {
        $input = [
            'documento' => '123.456.789-10',
            'outro' => '987.654.321-00',
        ];

        $clean = $this->sanitizer->sanitize($input);

        $this->assertSame('***.***.789-10', $clean['documento']);
        $this->assertSame('***.***.321-00', $clean['outro']);
    }

    public function testSanitizesNestedArraysRecursively(): void
    {
        $input = [
            'dados' => [
                'usuario' => [
                    'nome' => 'Heliomar',
                    'senha' => '123456',
                    'contato' => [
                        'email' => 'dev@empresa.com',
                    ],
                ],
            ],
        ];

        $clean = $this->sanitizer->sanitize($input);

        $this->assertSame('Heliomar', $clean['dados']['usuario']['nome']);
        $this->assertSame('********', $clean['dados']['usuario']['senha']);
        $this->assertSame('d**@empresa.com', $clean['dados']['usuario']['contato']['email']);
    }
}
