<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Tests\Unit;

use Exception;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Handlers\FilelogHandler;
use HeliomarPM\ErrorTracker\Severity;
use PHPUnit\Framework\TestCase;

class FilelogHandlerTest extends TestCase
{
    private string $tempLogDir;

    protected function setUp(): void
    {
        $this->tempLogDir = sys_get_temp_dir() . '/error_tracker_test_logs_' . uniqid();
        if (!is_dir($this->tempLogDir)) {
            mkdir($this->tempLogDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempLogDir)) {
            $files = glob($this->tempLogDir . '/*');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
            @rmdir($this->tempLogDir);
        }
    }

    public function testLogsAllSeveritiesByDefault(): void
    {
        $handler = new FilelogHandler($this->tempLogDir);
        $context = new ErrorContext(new Exception('Info test'), [], Severity::INFO);

        $handler->report($context);

        $expectedFile = $handler->getLogFilename($context);
        $this->assertFileExists($expectedFile);

        $content = file_get_contents($expectedFile);
        $this->assertIsString($content);
        $this->assertStringContainsString('Info test', $content);
        $this->assertStringContainsString('[INFO]', $content);
    }

    public function testFiltersByNotifySeverities(): void
    {
        $handler = new FilelogHandler(
            logDirectory: $this->tempLogDir,
            notifySeverities: [Severity::CRITICAL, Severity::WARNING]
        );

        // 1. INFO não deve gravar arquivo
        $infoContext = new ErrorContext(new Exception('Info notice'), [], Severity::INFO);
        $handler->report($infoContext);
        $this->assertFileDoesNotExist($handler->getLogFilename($infoContext));

        // 2. CRITICAL deve gravar arquivo
        $critContext = new ErrorContext(new Exception('Critical crash'), [], Severity::CRITICAL);
        $handler->report($critContext);
        $this->assertFileExists($handler->getLogFilename($critContext));

        $content = file_get_contents($handler->getLogFilename($critContext));
        $this->assertIsString($content);
        $this->assertStringContainsString('Critical crash', $content);
    }

    public function testCustomFormatter(): void
    {
        $handler = new FilelogHandler(
            logDirectory: $this->tempLogDir,
            formatter: fn(ErrorContext $ctx) => "JSON: {$ctx->message}"
        );

        $context = new ErrorContext(new Exception('Custom formatted'), [], Severity::WARNING);
        $entry = $handler->renderEntry($context);

        $this->assertSame('JSON: Custom formatted', $entry);
    }
}
