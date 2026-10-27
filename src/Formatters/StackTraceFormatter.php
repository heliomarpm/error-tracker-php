<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Formatters;

use Throwable;

/**
 * Formatador auxiliar de stack traces e normalização de caminhos.
 *
 * @internal Esta classe é de uso exclusivo interno da biblioteca e pode sofrer alterações sem aviso prévio.
 */
final class StackTraceFormatter
{
    /**
     * Cache estático em memória das raízes de diretório padrão pré-computadas.
     *
     * @var array<string>|null
     */
    private static ?array $cachedDefaultRoots = null;

    /**
     * Limpa o cache estático de raízes pré-computadas.
     */
    public static function clearCache(): void
    {
        self::$cachedDefaultRoots = null;
    }

    /**
     * Formata o stack trace de forma enxuta e legível, removendo paths absolutos do servidor.
     *
     * @param Throwable $e Exceção ou erro a ser formatado.
     * @param bool $includeCall Se true, inclui a chamada do método/função (ex.: ": Class->method()").
     * @param string|null $basePath Caminho base customizado a ser removido das trilhas absolutas.
     */
    public static function format(Throwable $e, bool $includeCall = true, ?string $basePath = null): string
    {
        $originFile = self::normalizePath($e->getFile(), $basePath);
        $originLine = $e->getLine();
        $detail = "{$originFile}({$originLine})";

        $backtrace = $e->getTrace();
        foreach ($backtrace as $trace) {
            if (!isset($trace['file'], $trace['line'])) {
                continue;
            }

            // Exclui apenas o exato ponto de origem do erro (mesmo arquivo E mesma linha)
            if ($trace['file'] === $e->getFile() && $trace['line'] === $e->getLine()) {
                continue;
            }

            $traceFile = self::normalizePath((string)$trace['file'], $basePath);
            $line = $trace['line'];

            $call = '';
            if ($includeCall && isset($trace['function'])) {
                $class = $trace['class'] ?? '';
                $type = $trace['type'] ?? '';
                $call = ": {$class}{$type}{$trace['function']}()";
            }

            $detail .= "\n    at {$traceFile}({$line}){$call}";
        }

        return $detail;
    }

    /**
     * Remove o caminho base absoluto do servidor ou diretório de trabalho para gerar paths relativos legíveis.
     */
    public static function normalizePath(string $path, ?string $basePath = null): string
    {
        $normalized = str_replace('\\', '/', $path);
        $roots = self::resolveRoots($basePath);

        foreach ($roots as $root) {
            $trimmedRoot = rtrim($root, '/');
            if ($trimmedRoot !== '' && str_starts_with(strtolower($normalized), strtolower($trimmedRoot))) {
                $normalized = substr($normalized, strlen($trimmedRoot));
                break;
            }
        }

        return ltrim($normalized, '/');
    }

    /**
     * Resolve e ordena as raízes de diretório com cache para as raízes padrão do ambiente.
     *
     * @return array<string>
     */
    private static function resolveRoots(?string $basePath = null): array
    {
        if (self::$cachedDefaultRoots === null) {
            $defaultRoots = [];
            if (!empty($_SERVER['DOCUMENT_ROOT'])) {
                $defaultRoots[] = str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']);
            }
            $cwd = getcwd();
            if ($cwd !== false) {
                $defaultRoots[] = str_replace('\\', '/', $cwd);
            }

            $defaultRoots = array_values(array_unique(array_filter($defaultRoots)));
            usort($defaultRoots, fn(string $a, string $b): int => strlen($b) <=> strlen($a));
            self::$cachedDefaultRoots = $defaultRoots;
        }

        if ($basePath === null || $basePath === '') {
            return self::$cachedDefaultRoots;
        }

        $normalizedBase = str_replace('\\', '/', $basePath);
        $roots = array_merge([$normalizedBase], self::$cachedDefaultRoots);
        $roots = array_values(array_unique(array_filter($roots)));
        usort($roots, fn(string $a, string $b): int => strlen($b) <=> strlen($a));

        return $roots;
    }
}
