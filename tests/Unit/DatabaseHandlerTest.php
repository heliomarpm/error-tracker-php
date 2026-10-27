<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Tests\Unit;

use ErrorException;
use Exception;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Handlers\DatabaseHandler;
use HeliomarPM\ErrorTracker\Severity;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DatabaseHandlerTest extends TestCase
{
    private PDO $pdo;
    private DatabaseHandler $handler;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->handler = new DatabaseHandler(
            pdo: $this->pdo,
            tableName: 'test_errors',
            autoCreate: true
        );
    }

    public function testInsertsInitialErrorAndIncrementsIncidentsOnRecurrence(): void
    {
        $exception = new ErrorException('Falha de conexão com serviço', 500, E_ERROR, '/app/Order.php', 42);
        $context = new ErrorContext($exception, ['order_id' => 1234], Severity::CRITICAL);

        // 1ª Ocorrência
        $this->handler->report($context);

        $stmt = $this->pdo->query('SELECT * FROM test_errors WHERE hash = ' . $this->pdo->quote($context->hash));
        $this->assertNotFalse($stmt);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($row);
        $this->assertSame($context->hash, $row['hash']);
        $this->assertSame('ErrorTracker', $row['app_name']);
        $this->assertSame('critical', $row['severity']);
        $this->assertSame('ErrorException', $row['error_class']);
        $this->assertNull($row['previous_class']);
        $this->assertSame('Falha de conexão com serviço', $row['message']);
        $this->assertSame('app/Order.php', $row['file']);
        $this->assertSame(42, (int)$row['line']);
        $this->assertSame('CLI', $row['method']);
        $this->assertSame(1, (int)$row['incidents']);

        // 2ª Ocorrência do mesmo erro (mesmo hash)
        $this->handler->report($context);

        $stmt2 = $this->pdo->query('SELECT incidents FROM test_errors WHERE hash = ' . $this->pdo->quote($context->hash));
        $this->assertNotFalse($stmt2);
        $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);

        $this->assertSame(2, (int)$row2['incidents']);
    }

    public function testInsertsChainedExceptionWithUserDetails(): void
    {
        $root = new RuntimeException('Falha interna de disco', 500);
        $exception = new Exception('Erro fatal no checkout', 503, $root);
        $context = new ErrorContext(
            $exception,
            [
                'post' => ['valor' => 199.90],
                'context' => ['user' => 'Heliomar Dev'],
            ],
            Severity::CRITICAL
        );

        $this->handler->report($context);

        $stmt = $this->pdo->query('SELECT * FROM test_errors WHERE hash = ' . $this->pdo->quote($context->hash));
        $this->assertNotFalse($stmt);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($row);
        $this->assertSame('Exception', $row['error_class']);
        $this->assertSame('RuntimeException', $row['previous_class']);
        $this->assertSame('Heliomar Dev', $row['user_name']);
        $this->assertStringContainsString('199.9', $row['payload']);
    }

    public function testFiltersBySeverity(): void
    {
        $filteredHandler = new DatabaseHandler(
            $this->pdo,
            tableName: 'test_errors_filter',
            autoCreate: true,
            notifySeverities: [Severity::CRITICAL, Severity::WARNING]
        );

        // 1. Erro com severidade INFO (deve ser descartado)
        $infoContext = new ErrorContext(new Exception('Info message'), [], Severity::INFO);
        $filteredHandler->report($infoContext);

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM test_errors_filter');
        $this->assertNotFalse($stmt);
        $this->assertSame(0, (int)$stmt->fetchColumn());

        // 2. Erro com severidade WARNING (deve ser persistido)
        $warningContext = new ErrorContext(new Exception('Warning message'), [], Severity::WARNING);
        $filteredHandler->report($warningContext);

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM test_errors_filter');
        $this->assertNotFalse($stmt);
        $this->assertSame(1, (int)$stmt->fetchColumn());
    }

    public function testUsesHandlerUserNameFallbackWhenContextHasNoUser(): void
    {
        $handler = new DatabaseHandler(
            $this->pdo,
            tableName: 'test_errors_fallback',
            autoCreate: true,
            userName: 'operador_default'
        );

        $context = new ErrorContext(new Exception('Erro sem user no context'), [], Severity::CRITICAL);
        $handler->report($context);

        $stmt = $this->pdo->query('SELECT user_name FROM test_errors_fallback WHERE hash = ' . $this->pdo->quote($context->hash));
        $this->assertNotFalse($stmt);
        $this->assertSame('operador_default', $stmt->fetchColumn());
    }

    public function testUsesHandlerUserResolverFallbackWhenContextHasNoUser(): void
    {
        $currentUser = 'usuario_sessao_123';
        $handler = new DatabaseHandler(
            $this->pdo,
            tableName: 'test_errors_resolver',
            autoCreate: true,
            userResolver: fn() => $currentUser
        );

        $context = new ErrorContext(new Exception('Erro dinâmico sem user'), [], Severity::CRITICAL);
        $handler->report($context);

        $stmt = $this->pdo->query('SELECT user_name FROM test_errors_resolver WHERE hash = ' . $this->pdo->quote($context->hash));
        $this->assertNotFalse($stmt);
        $this->assertSame('usuario_sessao_123', $stmt->fetchColumn());
    }

    public function testPersistsCustomAppName(): void
    {
        $exception = new RuntimeException('Erro de microsserviço');
        $context = new ErrorContext($exception, [], Severity::CRITICAL, appName: 'Checkout Service');

        $this->handler->report($context);

        $stmt = $this->pdo->query('SELECT app_name FROM test_errors WHERE hash = ' . $this->pdo->quote($context->hash));
        $this->assertNotFalse($stmt);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($row);
        $this->assertSame('Checkout Service', $row['app_name']);
    }

    public function testBuildRecordIncludesAppName(): void
    {
        $context = new ErrorContext(new Exception('Teste'), [], Severity::WARNING, appName: 'Portal Admin');
        $record = $this->handler->buildRecord($context);

        $this->assertArrayHasKey('app_name', $record);
        $this->assertSame('Portal Admin', $record['app_name']);
    }
}
