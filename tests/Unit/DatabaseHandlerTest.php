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
}
