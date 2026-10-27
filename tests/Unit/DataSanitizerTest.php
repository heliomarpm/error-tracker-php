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

    public function testLimitsRecursionDepthAtMaxDepth(): void
    {
        $deepInput = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'level5' => [
                                'level6' => 'valor ultra profundo',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $clean = $this->sanitizer->sanitize($deepInput);

        $this->assertIsArray($clean['level1']);
        $this->assertIsArray($clean['level1']['level2']);
        $this->assertIsArray($clean['level1']['level2']['level3']);
        $this->assertIsArray($clean['level1']['level2']['level3']['level4']);
        $this->assertSame('[DEPTH_LIMIT]', $clean['level1']['level2']['level3']['level4']['level5']);
    }

    public function testCustomMaxDepthConfigurable(): void
    {
        $sanitizer = new DataSanitizer(maxDepth: 2);
        $input = [
            'level1' => [
                'level2' => [
                    'item' => 'segredo',
                ],
            ],
        ];

        $clean = $sanitizer->sanitize($input);
        $this->assertSame('[DEPTH_LIMIT]', $clean['level1']['level2']);
    }

    public function testSkipsRegexChecksForExcessivelyLongStrings(): void
    {
        $longString = str_repeat('abc ', 120) . ' 123.456.789-10 ' . str_repeat('def ', 50);
        $this->assertGreaterThan(500, strlen($longString));

        $clean = $this->sanitizer->sanitize(['payload' => $longString]);

        $this->assertSame($longString, $clean['payload']);

        $shortString = '123.456.789-10';
        $cleanShort = $this->sanitizer->sanitize(['payload' => $shortString]);
        $this->assertSame('***.***.789-10', $cleanShort['payload']);
    }

    public function testCustomMaskedKeysCanBeAddedViaConstructorAndMethod(): void
    {
        $sanitizer = new DataSanitizer(['api_key', 'cartao_credito']);

        $input = [
            'api_key' => 'meu-token-secreto-123',
            'cartao_credito' => '1234',
            'publico' => 'visivel',
        ];

        $clean = $sanitizer->sanitize($input);
        $this->assertSame('********', $clean['api_key']);
        $this->assertSame('********', $clean['cartao_credito']);
        $this->assertSame('visivel', $clean['publico']);

        $sanitizer->addMaskedKeys(['pin_acesso']);
        $clean2 = $sanitizer->sanitize(['pin_acesso' => '9988']);
        $this->assertSame('********', $clean2['pin_acesso']);
    }
}
