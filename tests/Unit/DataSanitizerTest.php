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

    public function testMasksConnectionKeysCompletely(): void
    {
        $input = [
            'dsn' => 'mysql:host=db.prod.local;dbname=salvar_app',
            'db_pass' => 'secreto123',
            'db_user' => 'root_prod',
            'db_host' => '10.0.0.15',
            'database_url' => 'mysql://admin:pass@db.local:3306/app',
            'smtp_pass' => 'smtp-secret',
            'connection_string' => 'Server=sql.local;Database=erp;UID=sa;PWD=123;',
            'pwd' => 'pass123',
            'api_key' => 'ak_live_987654321',
            'user' => 'heliomar',
        ];

        $clean = $this->sanitizer->sanitize($input);

        $this->assertSame('********', $clean['dsn']);
        $this->assertSame('********', $clean['db_pass']);
        $this->assertSame('********', $clean['db_user']);
        $this->assertSame('********', $clean['db_host']);
        $this->assertSame('********', $clean['database_url']);
        $this->assertSame('********', $clean['smtp_pass']);
        $this->assertSame('********', $clean['connection_string']);
        $this->assertSame('********', $clean['pwd']);
        $this->assertSame('********', $clean['api_key']);
        $this->assertSame('heliomar', $clean['user']);
    }

    public function testSanitizeTextMasksConstructorAndFunctionConnectionArgsInTraces(): void
    {
        $traceWithArgs = <<<'TRACE'
Fatal error: Uncaught PDOException: SQLSTATE[HY000] [2002] Connection refused in /app/Database.php:15
Stack trace:
#0 /app/Database.php(15): PDO->__construct('mysql:host=192.168.1.50;port=3306;dbname=salvar_prod', 'admin_db', 'SuperSenha@2026')
#1 /app/Database.php(20): PDO->__construct('mysql:host=db.local;dbname=app', 'root', Object(SensitiveParameterValue))
#2 /app/Legacy.php(12): mysqli_connect('10.0.0.5', 'usuario', 'minhaSenha')
#3 /app/Pg.php(8): pg_connect('host=10.0.0.8 port=5432 dbname=vendas user=pg password=pgpass')
#4 /app/Mail.php(30): SmtpTransport->__construct('smtp.titan.email', 465, 'alertas@empresa.com', 'SmtpPass99')
TRACE;

        $masked = $this->sanitizer->sanitizeText($traceWithArgs);

        $this->assertStringNotContainsString('192.168.1.50', $masked);
        $this->assertStringNotContainsString('SuperSenha@2026', $masked);
        $this->assertStringNotContainsString('admin_db', $masked);
        $this->assertStringNotContainsString('minhaSenha', $masked);
        $this->assertStringNotContainsString('pgpass', $masked);
        $this->assertStringNotContainsString('SmtpPass99', $masked);
        $this->assertStringContainsString('PDO->__construct([CONNECTION_ARGS_MASKED])', $masked);
        $this->assertStringContainsString('mysqli_connect([CONNECTION_ARGS_MASKED])', $masked);
        $this->assertStringContainsString('pg_connect([CONNECTION_ARGS_MASKED])', $masked);
        $this->assertStringContainsString('SmtpTransport->__construct([CONNECTION_ARGS_MASKED])', $masked);
    }

    public function testSanitizeTextMasksConnectionUrisDsnsKeyValueDatabaseErrorsAndTokens(): void
    {
        $text = implode("\n", [
            'MYSQL: mysql://admin:Segredo123@db.internal:3306/salvar_prod',
            'PG: postgres://pguser:PgPass!@pg.internal:5432/erp',
            'REDIS: redis://:RedisPass99@redis.internal:6379/0',
            'MONGO: mongodb+srv://atlas_user:AtlasPass@cluster0.mongodb.net/app',
            'PDO_DSN: mysql:host=10.0.1.25;port=3306;dbname=clientes;charset=utf8mb4',
            'SQLSRV_DSN: sqlsrv:Server=sql.internal,1433;Database=erp_prod;UID=sa;PWD=SqlSecret!',
            'LOOSE_KV: host=10.20.30.40 dbname=financeiro password=MinhaSenha123 api_key=sk_live_abc123',
            "ERR_MYSQL_AUTH: Access denied for user 'root'@'192.168.1.50' (using password: YES)",
            "ERR_MYSQL_DB: Unknown database 'salvar_prod'",
            'ERR_PG_AUTH: password authentication failed for user "postgres"',
            'ERR_PG_HOST: could not translate host name "db.prod.local" to address',
            'ERR_DNS: php_network_getaddresses: getaddrinfo for db.prod.local failed: Name or service not known',
            'AUTH_HDR: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.payload.sig Basic dXNlcjpzZWNyZXQ=',
            'SAFE_URL: https://app.example.com/checkout?email=contato@example.com',
        ]);

        $masked = $this->sanitizer->sanitizeText($text);

        $this->assertStringContainsString('mysql://***:********@********', $masked);
        $this->assertStringContainsString('postgres://***:********@********', $masked);
        $this->assertStringContainsString('redis://***:********@********', $masked);
        $this->assertStringContainsString('mongodb+srv://***:********@********', $masked);
        $this->assertStringContainsString('mysql:host=********;port=********;dbname=********;charset=utf8mb4', $masked);
        $this->assertStringContainsString('sqlsrv:Server=********;Database=********;UID=********;PWD=********', $masked);
        $this->assertStringContainsString('host=******** dbname=******** password=******** api_key=********', $masked);
        $this->assertStringContainsString("Access denied for user '***'@'********'", $masked);
        $this->assertStringContainsString("Unknown database '********'", $masked);
        $this->assertStringContainsString('authentication failed for user "***"', $masked);
        $this->assertStringContainsString('could not translate host name "********"', $masked);
        $this->assertStringContainsString('getaddrinfo for ******** failed', $masked);
        $this->assertStringContainsString('Bearer ********', $masked);
        $this->assertStringContainsString('Basic ********', $masked);
        $this->assertStringContainsString('https://app.example.com/checkout?email=contato@example.com', $masked);

        foreach (['Segredo123', 'PgPass!', 'RedisPass99', 'AtlasPass', '10.0.1.25', 'SqlSecret!', 'MinhaSenha123', 'sk_live_abc123', '192.168.1.50', 'salvar_prod', 'db.prod.local', 'dXNlcjpzZWNyZXQ='] as $secret) {
            $this->assertStringNotContainsString($secret, $masked);
        }
    }

    public function testSanitizeMasksConnectionDataInStringsLongerThan500Chars(): void
    {
        $longConnectionLog = str_repeat('Log entry padding. ', 30) . ' mysql://user:Secret999@10.0.0.50:3306/prod_db';
        $this->assertGreaterThan(500, strlen($longConnectionLog));

        $clean = $this->sanitizer->sanitize(['log' => $longConnectionLog]);

        $this->assertStringNotContainsString('Secret999', $clean['log']);
        $this->assertStringNotContainsString('10.0.0.50', $clean['log']);
        $this->assertStringContainsString('mysql://***:********@********', $clean['log']);
    }

    public function testCanDisableSanitizationViaConstructorAndSetter(): void
    {
        $raw = [
            'password' => 'minhaSenha123',
            'dsn' => 'mysql:host=127.0.0.1;dbname=salvar',
            'email' => 'dev@empresa.com',
            'trace' => "PDO->__construct('mysql:host=127.0.0.1', 'root', '1234')",
        ];

        $disabled = new DataSanitizer(enabled: false);
        $this->assertFalse($disabled->isEnabled());
        $this->assertSame($raw, $disabled->sanitize($raw));
        $this->assertSame($raw['trace'], $disabled->sanitizeText($raw['trace']));

        $sanitizer = new DataSanitizer();
        $this->assertTrue($sanitizer->isEnabled());
        $sanitizer->setEnabled(false);
        $this->assertFalse($sanitizer->isEnabled());
        $this->assertSame($raw, $sanitizer->sanitize($raw));
        $this->assertSame($raw['trace'], $sanitizer->sanitizeText($raw['trace']));
    }
}
