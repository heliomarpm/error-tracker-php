<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker;

use Throwable;

class ErrorContext
{
    public readonly Severity $severity;
    public readonly string $message;
    public readonly int|string $code;
    public readonly string $file;
    public readonly int $line;
    public readonly string $trace;
    public readonly array $requestData;
    public readonly string $hash;

    public readonly string $exceptionClass;
    public readonly ?string $previousClass;
    public readonly ?string $previousMessage;
    public readonly string $requestMethod;
    public readonly string $requestUri;
    public readonly string $serverName;
    public readonly string $phpVersion;
    public readonly string $memoryUsage;
    public readonly ?string $userName;

    public function __construct(
        Throwable $e,
        array $sanitizedRequest,
        Severity $severity = Severity::CRITICAL,
        ?string $userName = null
    ) {
        $this->severity = $severity;
        $this->message = $e->getMessage();
        $this->code = $e->getCode();
        $this->file = $e->getFile();
        $this->line = $e->getLine();
        $this->trace = $e->getTraceAsString();
        $this->requestData = $sanitizedRequest;

        $this->exceptionClass = get_class($e);

        $prev = $e->getPrevious();
        $this->previousClass = $prev !== null ? get_class($prev) : null;
        $this->previousMessage = $prev !== null ? $prev->getMessage() : null;

        $this->requestMethod = $_SERVER['REQUEST_METHOD'] ?? (PHP_SAPI === 'cli' ? 'CLI' : 'GET');
        $this->requestUri = $_SERVER['REQUEST_URI'] ?? (PHP_SAPI === 'cli' ? ($GLOBALS['argv'][0] ?? 'cli') : '/');
        $this->serverName = $_SERVER['SERVER_NAME'] ?? (gethostname() ?: 'localhost');
        $this->phpVersion = PHP_VERSION;

        $bytes = memory_get_peak_usage(true);
        if ($bytes >= 1048576) {
            $this->memoryUsage = sprintf('%.2f MB', $bytes / 1048576);
        } elseif ($bytes >= 1024) {
            $this->memoryUsage = sprintf('%.2f KB', $bytes / 1024);
        } else {
            $this->memoryUsage = $bytes . ' B';
        }

        // Resolução do nome do usuário autenticado ou no contexto
        $resolvedUser = $userName;
        if ($resolvedUser === null && isset($sanitizedRequest['context']) && is_array($sanitizedRequest['context'])) {
            $ctxUser = $sanitizedRequest['context']['user'] ?? $sanitizedRequest['context']['user_name'] ?? $sanitizedRequest['context']['usuario'] ?? null;
            if (is_string($ctxUser)) {
                $resolvedUser = $ctxUser;
            } elseif (is_array($ctxUser)) {
                $resolvedUser = (string)($ctxUser['name'] ?? ($ctxUser['email'] ?? ($ctxUser['id'] ?? '')));
            }
        }

        if ($resolvedUser === null && isset($_SESSION)) {
            $sessUser = $_SESSION['user'] ?? ($_SESSION['usuario'] ?? null);
            if (is_string($sessUser)) {
                $resolvedUser = $sessUser;
            } elseif (is_array($sessUser)) {
                $resolvedUser = (string)($sessUser['name'] ?? ($sessUser['email'] ?? ($sessUser['username'] ?? '')));
            }
        }

        $this->userName = !empty($resolvedUser) ? $resolvedUser : null;

        // O segredo da deduplicação: Um hash único para este erro exato
        $this->hash = md5($this->file . $this->line . $this->message);
    }
}
