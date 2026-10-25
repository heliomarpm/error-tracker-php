<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Tests\Unit;

use HeliomarPM\ErrorTracker\Cache\FileCache;
use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\ErrorTracker;
use HeliomarPM\ErrorTracker\Severity;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use RuntimeException;

final class ErrorTrackerTest extends TestCase
{
    private string $cacheDir;
    private FileCache $cache;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/error_tracker_et_test_' . uniqid();
        $this->cache = new FileCache($this->cacheDir);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        if (is_dir($this->cacheDir)) {
            @rmdir($this->cacheDir);
        }
    }

    public function testDispatchesErrorToRegisteredHandlers(): void
    {
        $tracker = new ErrorTracker($this->cache);

        $reportedContext = null;
        $handlerMock = new class (function (ErrorContext $c) use (&$reportedContext) {
            $reportedContext = $c;
        }) implements HandlerInterface {
            public function __construct(private $callback) {}
            public function report(ErrorContext $context): void
            {
                ($this->callback)($context);
            }
        };

        $tracker->addHandler($handlerMock);
        $tracker->log(LogLevel::ERROR, 'Falha de teste manual');

        $this->assertNotNull($reportedContext);
        $this->assertSame('Falha de teste manual', $reportedContext->message);
        $this->assertSame(Severity::CRITICAL, $reportedContext->severity);
    }

    public function testSuppressesDuplicateErrorsWithinCacheTtl(): void
    {
        $tracker = new ErrorTracker($this->cache, 3600);

        $callCount = 0;
        $handler = new class (function () use (&$callCount) {
            $callCount++;
        }) implements HandlerInterface {
            public function __construct(private $callback) {}
            public function report(ErrorContext $context): void
            {
                ($this->callback)($context);
            }
        };

        $tracker->addHandler($handler);

        $exception = new RuntimeException('Erro duplicado teste');

        // Primeira chamada
        $tracker->handleException($exception);
        $this->assertSame(1, $callCount);

        // Segunda chamada (idêntica)
        $tracker->handleException($exception);
        $this->assertSame(1, $callCount, 'O segundo alerta deveria ser suprimido pelo cache anti-flood.');
    }

    public function testLogsWithDifferentSeverityLevels(): void
    {
        $tracker = new ErrorTracker($this->cache);

        $severities = [];
        $handler = new class (function (ErrorContext $c) use (&$severities) {
            $severities[] = $c->severity;
        }) implements HandlerInterface {
            public function __construct(private $callback) {}
            public function report(ErrorContext $context): void
            {
                ($this->callback)($context);
            }
        };

        $tracker->addHandler($handler);

        $tracker->info('Info log');
        $tracker->warning('Warning log');
        $tracker->notice('Notice log');

        $this->assertContains(Severity::INFO, $severities);
        $this->assertContains(Severity::WARNING, $severities);
        $this->assertContains(Severity::NOTICE, $severities);
    }

    public function testInfoLogResolvesCallerOriginAndIsNotMarkedAsException(): void
    {
        $tracker = new ErrorTracker($this->cache);

        /** @var ErrorContext|null $reportedContext */
        $reportedContext = null;
        $handler = new class (function (ErrorContext $c) use (&$reportedContext) {
            $reportedContext = $c;
        }) implements HandlerInterface {
            public function __construct(private $callback) {}
            public function report(ErrorContext $context): void
            {
                ($this->callback)($context);
            }
        };

        $tracker->addHandler($handler);

        // Chamada direta do método info com contexto de usuário
        $line = __LINE__ + 1;
        $tracker->info('Tentativa de login com credencial inválida', ['username' => 'ana_tech']);

        $this->assertNotNull($reportedContext);
        $this->assertSame('Tentativa de login com credencial inválida', $reportedContext->message);
        $this->assertSame(Severity::INFO, $reportedContext->severity);
        $this->assertFalse($reportedContext->isException);
        $this->assertSame('INFO', $reportedContext->exceptionClass);
        $this->assertSame('ana_tech', $reportedContext->userName);
        $this->assertSame(__FILE__, $reportedContext->file);
        $this->assertSame($line, $reportedContext->line);
        $this->assertSame('', $reportedContext->trace);
    }
}
