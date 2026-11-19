<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Formatters;

use HeliomarPM\ErrorTracker\ErrorContext;

/**
 * Motor interno de renderização de templates baseados em tags e condicionais.
 *
 * @internal Esta classe é de uso exclusivo interno da biblioteca e pode sofrer alterações sem aviso prévio.
 */
final class TemplateRenderer
{
    /**
     * Cache estático em memória para conteúdo de arquivos de template.
     *
     * @var array<string, string>
     */
    private static array $fileCache = [];

    /**
     * Limpa o cache estático de templates em memória.
     */
    public static function clearCache(): void
    {
        self::$fileCache = [];
    }

    /**
     * Retorna a quantidade de templates em cache no momento.
     */
    public static function getCacheCount(): int
    {
        return count(self::$fileCache);
    }

    /**
     * Renderiza um template substituindo tags no formato {{tag}} pelos valores do ErrorContext,
     * suportando também blocos condicionais <!-- IF_COND -->...<!-- /IF_COND -->.
     *
     * @param string $templateOrPath Caminho do arquivo de template ou string de template.
     * @param ErrorContext $context Contexto do erro capturado.
     * @param array<string, string|int|float|null> $extraTags Tags adicionais para substituição.
     */
    public function render(string $templateOrPath, ErrorContext $context, array $extraTags = []): string
    {
        $content = $this->loadTemplateContent($templateOrPath);

        $payloadJson = json_encode(
            $context->requestData,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) ?: '{}';

        $tags = array_merge([
            // Identificação do Erro
            'timestamp' => date('Y-m-d H:i:s'),
            'severity' => strtoupper($context->severity->name),
            'severity_value' => strtolower($context->severity->value),
            'is_exception' => $context->isException ? '1' : '',
            'is_message' => !$context->isException ? '1' : '',
            'has_trace' => ($context->isException && !empty($context->trace)) ? '1' : '',
            'error_title' => $context->exceptionClass,
            'error_class' => $context->exceptionClass,
            'class' => $context->exceptionClass,
            'message' => $context->message,
            'error_message' => $context->message,
            'code' => (string)$context->code,

            // Localização e Origem
            'file' => $context->file,
            'relative_file' => $context->relativeFile,
            'error_file' => $context->relativeFile,
            'line' => (string)$context->line,
            'error_line' => (string)$context->line,
            'trace' => $context->trace,
            'stack_trace' => $context->trace,
            'compact_trace' => $context->compactTrace,
            'detailed_trace' => $context->detailedTrace,
            'raw_trace' => $context->rawTrace,
            'hash' => $context->hash,

            // Causa Raiz
            'previous_class' => (string)($context->previousClass ?? ''),
            'previous_message' => (string)($context->previousMessage ?? ''),

            // Contexto da Requisição e Ambiente
            'app_name' => $context->appName,
            'environment' => $context->environment,
            'request_method' => $context->requestMethod,
            'request_uri' => $context->requestUri,
            'server_name' => $context->serverName,
            'php_version' => $context->phpVersion,
            'memory_usage' => $context->memoryUsage,
            'user' => (string)($context->userName ?? 'Anônimo / Não autenticado'),
            'usuario_nome' => (string)($context->userName ?? 'Anônimo / Não autenticado'),

            // IP e Referer
            'client_ip' => $context->clientIp,
            'referer' => (string)($context->referer ?? ''),

            // Metadados SQL
            'sql' => (string)($context->sqlQuery ?? ''),
            'sql_params' => (string)($context->sqlParams ?? ''),

            // Payload Sanitizado
            'payload' => $payloadJson,
        ], $extraTags);

        // 1. Processamento de blocos condicionais <!-- IF_COND -->...<!-- /IF_COND -->
        $content = $this->renderConditionals($content, $tags, $context);

        // 2. Interpolação de tags com duplas chaves {{tag}}
        $replacements = [];
        foreach ($tags as $key => $value) {
            $replacements['{{' . $key . '}}'] = (string)($value ?? '');
        }

        return strtr($content, $replacements);
    }

    /**
     * Processa e avalia blocos condicionais <!-- IF_COND -->...<!-- /IF_COND -->.
     *
     * @param array<string, string|int|float|null> $tags
     */
    private function renderConditionals(string $content, array $tags, ErrorContext $context): string
    {
        $regex = '/(?:^[ \t]*)?<!--\s*IF_([A-Za-z0-9_]+)\s*-->\r?\n?(.*?)<!--\s*\/IF_\1\s*-->[ \t]*(?:\r?\n)?/ms';

        // Suporta até 5 níveis de blocos condicionais aninhados
        $iterations = 0;
        while (preg_match($regex, $content) && $iterations < 5) {
            $content = (string)preg_replace_callback(
                $regex,
                function (array $matches) use ($tags, $context): string {
                    $cond = strtoupper(trim($matches[1]));
                    $inner = $matches[2];

                    $isTruthy = match ($cond) {
                        'ORIGIN' => (!empty($tags['error_file']) || !empty($tags['file'])) && (!empty($tags['error_line']) || !empty($tags['line'])),
                        'PREVIOUS' => !empty($tags['previous_class']) || !empty($tags['previous_message']),
                        'USER', 'USUARIO', 'USUARIO_NOME' => ($context->userName !== null && $context->userName !== '')
                            || (!empty($tags['user']) && $tags['user'] !== 'Anônimo / Não autenticado')
                            || (!empty($tags['usuario_nome']) && $tags['usuario_nome'] !== 'Anônimo / Não autenticado'),
                        'EXCEPTION' => $context->isException,
                        'NOT_EXCEPTION', 'MESSAGE_ONLY' => !$context->isException,
                        'TRACE', 'STACK_TRACE' => $context->isException && !empty($tags['stack_trace']),
                        'PRODUCTION' => in_array(strtolower($context->environment), ['production', 'prod'], true),
                        'STAGING' => in_array(strtolower($context->environment), ['staging', 'stage'], true),
                        'DEVELOPMENT', 'DEV', 'LOCAL' => in_array(strtolower($context->environment), ['development', 'dev', 'local'], true),
                        'SQL' => !empty($context->sqlQuery),
                        'SQL_PARAMS' => !empty($context->sqlParams),
                        'REFERER' => !empty($context->referer),
                        default => !empty($tags[strtolower($cond)] ?? '') || !empty($tags[$cond] ?? ''),
                    };

                    return $isTruthy ? $inner : '';
                },
                $content
            );
            $iterations++;
        }

        return $content;
    }

    private function loadTemplateContent(string $templateOrPath): string
    {
        if (isset(self::$fileCache[$templateOrPath])) {
            return self::$fileCache[$templateOrPath];
        }

        if (is_file($templateOrPath)) {
            $content = file_get_contents($templateOrPath);
            return self::$fileCache[$templateOrPath] = ($content !== false ? $content : '');
        }

        return $templateOrPath;
    }
}
