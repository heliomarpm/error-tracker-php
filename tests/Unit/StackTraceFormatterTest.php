<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Tests\Unit;

use Exception;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Formatters\StackTraceFormatter;
use HeliomarPM\ErrorTracker\Severity;
use PHPUnit\Framework\TestCase;

final class StackTraceFormatterTest extends TestCase
{
    public function testFormatsTraceWithCallInfo(): void
    {
        $exception = $this->createNestedException();

        $traceWithCall = StackTraceFormatter::format($exception, includeCall: true);

        // Deve conter a linha de origem
        $this->assertStringContainsString('StackTraceFormatterTest.php', $traceWithCall);
        // Deve conter a marcação "at"
        $this->assertStringContainsString('    at ', $traceWithCall);
        // Deve conter a chamada do método
        $this->assertStringContainsString('createNestedException()', $traceWithCall);
    }

    public function testFormatsTraceWithoutCallInfo(): void
    {
        $exception = $this->createNestedException();

        $traceWithoutCall = StackTraceFormatter::format($exception, includeCall: false);

        // Deve conter o prefixo "at" com arquivo e linha
        $this->assertMatchesRegularExpression('/    at .+\(\d+\)/', $traceWithoutCall);
        // Não deve conter a assinatura de método
        $this->assertStringNotContainsString('createNestedException()', $traceWithoutCall);
    }

    public function testExcludesDuplicateOriginFrame(): void
    {
        $exception = new Exception('Erro direto');
        $formatted = StackTraceFormatter::format($exception, includeCall: true);

        $lines = explode("\n", $formatted);
        $firstLine = $lines[0]; // Origem

        // Nenhuma linha subsequente "at ..." deve ser idêntica à linha de origem
        for ($i = 1; $i < count($lines); $i++) {
            $frame = trim($lines[$i]);
            $this->assertNotSame('at ' . $firstLine, $frame);
        }
    }

    public function testNormalizesPathRemovingServerRoots(): void
    {
        $fakeDocRoot = 'D:/WORKS/DEV/WebApp/php/error-tracker';
        $fullPath = 'D:\WORKS\DEV\WebApp\php\error-tracker\src\Handlers\DatabaseHandler.php';

        $normalized = StackTraceFormatter::normalizePath($fullPath, $fakeDocRoot);
        $this->assertSame('src/Handlers/DatabaseHandler.php', $normalized);
    }

    public function testErrorContextExposesBothCompactAndDetailedTraces(): void
    {
        $exception = $this->createNestedException();
        $context = new ErrorContext($exception, [], Severity::CRITICAL);

        $this->assertNotEmpty($context->detailedTrace);
        $this->assertNotEmpty($context->compactTrace);
        $this->assertNotEmpty($context->rawTrace);
        $this->assertNotEmpty($context->relativeFile);

        $this->assertStringContainsString('createNestedException()', $context->detailedTrace);
        $this->assertStringNotContainsString('createNestedException()', $context->compactTrace);
    }

    public function testNormalizesPathWithCachedRoots(): void
    {
        StackTraceFormatter::clearCache();

        $cwd = getcwd();
        if ($cwd !== false) {
            $path = $cwd . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'ErrorTracker.php';
            $normalized = StackTraceFormatter::normalizePath($path);
            $this->assertSame('src/ErrorTracker.php', $normalized);

            // Segunda chamada reutiliza as raízes cacheadas
            $normalized2 = StackTraceFormatter::normalizePath($path);
            $this->assertSame('src/ErrorTracker.php', $normalized2);
        }

        StackTraceFormatter::clearCache();
    }

    private function createNestedException(): Exception
    {
        return new Exception('Falha simulada em teste');
    }
}
