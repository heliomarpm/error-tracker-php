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

        $bytes = memory_get_peak_usage(true);
        if ($bytes >= 1048576) {
            $this->memoryUsage = sprintf('%.2f MB', $bytes / 1048576);
        } elseif ($bytes >= 1024) {
            $this->memoryUsage = sprintf('%.2f KB', $bytes / 1024);
        } else {
            $this->memoryUsage = $bytes . ' B';
        }

        // Resolução do nome do usuário autenticado ou no contexto
        $resolvedUser = self::resolveUserString($userName);

        if ($resolvedUser === null && isset($sanitizedRequest['context']) && is_array($sanitizedRequest['context'])) {
            $ctxUser = $sanitizedRequest['context']['userName']
                ?? $sanitizedRequest['context']['user_name']
                ?? $sanitizedRequest['context']['username']
                ?? $sanitizedRequest['context']['user']
                ?? $sanitizedRequest['context']['usuario']
                ?? $sanitizedRequest['context']['usuario_nome']
                ?? null;
            $resolvedUser = self::resolveUserString($ctxUser);
        }

        if ($resolvedUser === null && isset($_SESSION)) {
            $sessUser = $_SESSION['userName']
                ?? $_SESSION['user_name']
                ?? $_SESSION['username']
                ?? $_SESSION['usuario_nome']
                ?? $_SESSION['nome_usuario']
                ?? $_SESSION['login']
                ?? ($_SESSION['user'] ?? ($_SESSION['usuario'] ?? null))
                ?? ($_SESSION['auth']['user'] ?? ($_SESSION['auth']['usuario'] ?? ($_SESSION['auth']['name'] ?? ($_SESSION['auth']['nome'] ?? null))))
                ?? null;

            $resolvedUser = self::resolveUserString($sessUser);
        }

        $this->userName = $resolvedUser;

        // O segredo da deduplicação: Um hash único para este erro exato
        $this->hash = md5($this->file . $this->line . $this->message);
    }

    /**
     * Extrai com segurança uma representação em string do usuário a partir de diversos tipos
     * (string, Stringable, int, array associativo ou entidade/objeto).
     */
    public static function resolveUserString(mixed $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if (is_string($user) || is_numeric($user)) {
            $trimmed = trim((string)$user);
            return $trimmed !== '' ? $trimmed : null;
        }

        if ($user instanceof Stringable) {
            $trimmed = trim((string)$user);
            return $trimmed !== '' ? $trimmed : null;
        }

        if (is_array($user)) {
            $val = $user['userName']
                ?? $user['user_name']
                ?? $user['username']
                ?? $user['nome']
                ?? $user['name']
                ?? $user['login']
                ?? $user['email']
                ?? $user['user']
                ?? $user['usuario']
                ?? $user['id']
                ?? null;

            return self::resolveUserString($val);
        }

        if (is_object($user)) {
            if (method_exists($user, 'getName')) {
                $name = $user->getName();
                if ($name !== null && $name !== '') {
                    return (string)$name;
                }
            }
            if (method_exists($user, 'getNome')) {
                $nome = $user->getNome();
                if ($nome !== null && $nome !== '') {
                    return (string)$nome;
                }
            }
            if (method_exists($user, 'getUserName')) {
                $u = $user->getUserName();
                if ($u !== null && $u !== '') {
                    return (string)$u;
                }
            }

            foreach (['userName', 'user_name', 'username', 'nome', 'name', 'login', 'email', 'user', 'usuario', 'id'] as $prop) {
                if (isset($user->{$prop}) && !empty($user->{$prop})) {
                    return (string)$user->{$prop};
                }
            }
        }

        return null;
    }
}
