<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker;

use HeliomarPM\ErrorTracker\Formatters\StackTraceFormatter;
use Stringable;
use Throwable;

final class ErrorContext
{
    public readonly Severity $severity;
    public readonly string $message;
    public readonly int|string $code;
    public readonly string $file;
    public readonly string $relativeFile;
    public readonly int $line;
    public readonly string $trace;
    public readonly string $rawTrace;
    public readonly string $compactTrace;
    public readonly string $detailedTrace;
    /** @var array<string|int, mixed> */
    public readonly array $requestData;
    public readonly string $hash;

    public readonly bool $isException;
    public readonly string $exceptionClass;
    public readonly ?string $previousClass;
    public readonly ?string $previousMessage;
    public readonly string $requestMethod;
    public readonly string $requestUri;
    public readonly string $serverName;
    public readonly string $appName;
    public readonly string $environment;
    public readonly string $phpVersion;
    public readonly string $memoryUsage;
    public readonly ?string $userName;
    public readonly string $clientIp;
    public readonly ?string $referer;
    public readonly ?string $sqlQuery;
    public readonly ?string $sqlParams;

    /**
     * @param array<string|int, mixed> $sanitizedRequest
     */
    public function __construct(
        Throwable $e,
        array $sanitizedRequest,
        Severity $severity = Severity::CRITICAL,
        ?string $userName = null,
        bool $includeCallInTrace = true,
        bool $isException = true,
        ?string $appName = null,
        ?string $environment = null,
        ?string $serverName = null
    ) {
        $this->isException = $isException;
        $this->severity = $severity;
        $this->message = $e->getMessage();
        $this->code = $e->getCode();
        $this->file = $e->getFile();
        $this->relativeFile = StackTraceFormatter::normalizePath($this->file);
        $this->line = $e->getLine();

        $this->rawTrace = $isException ? $e->getTraceAsString() : '';
        $this->compactTrace = $isException ? StackTraceFormatter::format($e, includeCall: false) : '';
        $this->detailedTrace = $isException ? StackTraceFormatter::format($e, includeCall: true) : '';
        $this->trace = $isException ? ($includeCallInTrace ? $this->detailedTrace : $this->compactTrace) : '';

        $this->requestData = $sanitizedRequest;

        $this->exceptionClass = $isException ? get_class($e) : strtoupper($severity->name);

        $prev = $isException ? $e->getPrevious() : null;
        $this->previousClass = $prev !== null ? get_class($prev) : null;
        $this->previousMessage = $prev !== null ? $prev->getMessage() : null;

        $this->requestMethod = $_SERVER['REQUEST_METHOD'] ?? (PHP_SAPI === 'cli' ? 'CLI' : 'GET');
        $this->requestUri = $_SERVER['REQUEST_URI'] ?? (PHP_SAPI === 'cli' ? ($GLOBALS['argv'][0] ?? 'cli') : '/');
        $this->serverName = !empty($serverName) ? $serverName : ($_SERVER['SERVER_NAME'] ?? (gethostname() ?: 'localhost'));
        $this->appName = !empty($appName) ? $appName : 'ErrorTracker';
        $env = !empty($environment) ? $environment : (getenv('APP_ENV') ?: (getenv('ENVIRONMENT') ?: 'production'));
        $this->environment = is_string($env) ? $env : 'production';
        $this->phpVersion = PHP_VERSION;
        $this->clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $this->referer = $_SERVER['HTTP_REFERER'] ?? null;

        // Extrai metadados SQL se disponíveis no context
        $ctx = $sanitizedRequest['context'] ?? [];
        $sql = null;
        $sqlParams = null;

        if (is_array($ctx)) {
            $sql = $ctx['sql'] ?? null;
            $rawParams = $ctx['sql_params'] ?? null;
            if ($rawParams !== null) {
                $sqlParams = is_string($rawParams) ? $rawParams : (json_encode($rawParams, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null);
            }
        }

        $this->sqlQuery = is_string($sql) && trim($sql) !== '' ? trim($sql) : null;
        $this->sqlParams = $sqlParams;

        $bytes = memory_get_peak_usage(true);
        if ($bytes >= 1048576) {
            $this->memoryUsage = sprintf('%.2f MB', $bytes / 1048576);
        } elseif ($bytes >= 1024) {
            $this->memoryUsage = sprintf('%.2f KB', $bytes / 1024);
        } else {
            $this->memoryUsage = $bytes . ' B';
        }

        // Atribuição do usuário: prioriza o parâmetro direto e depois o context fornecido
        $resolvedUser = $userName;
        if ($resolvedUser === null && isset($sanitizedRequest['context']) && is_array($sanitizedRequest['context'])) {
            $ctxUser = $sanitizedRequest['context']['user']
                ?? $sanitizedRequest['context']['userName']
                ?? $sanitizedRequest['context']['user_name']
                ?? $sanitizedRequest['context']['username']
                ?? null;
            if (is_string($ctxUser) || is_numeric($ctxUser) || $ctxUser instanceof Stringable) {
                $resolvedUser = trim((string)$ctxUser);
            }
        }
        $this->userName = is_string($resolvedUser) && trim($resolvedUser) !== '' ? trim($resolvedUser) : null;

        // O segredo da deduplicação: Um hash único para este erro exato
        $this->hash = md5($this->file . $this->line . $this->message);
    }
}
