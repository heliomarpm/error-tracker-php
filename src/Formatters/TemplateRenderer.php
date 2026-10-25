<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Formatters;

use HeliomarPM\ErrorTracker\ErrorContext;

class TemplateRenderer
{
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
        $content = is_file($templateOrPath) ? (file_get_contents($templateOrPath) ?: '') : $templateOrPath;

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
            'request_method' => $context->requestMethod,
            'method' => $context->requestMethod,
            'request_uri' => $context->requestUri,
            'uri' => $context->requestUri,
            'server_name' => $context->serverName,
            'server' => $context->serverName,
            'php_version' => $context->phpVersion,
            'memory_usage' => $context->memoryUsage,
            'memory' => $context->memoryUsage,
            'usuario_nome' => (string)($context->userName ?? 'Anônimo / Não autenticado'),
            'user' => (string)($context->userName ?? 'Anônimo / Não autenticado'),
            'user_name' => (string)($context->userName ?? 'Anônimo / Não autenticado'),

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
                        'USER', 'USUARIO', 'USUARIO_NOME' => $context->userName !== null && $context->userName !== '',
                        'EXCEPTION', 'IS_EXCEPTION' => $context->isException,
                        'NOT_EXCEPTION', 'MESSAGE_ONLY', 'IS_MESSAGE' => !$context->isException,
                        'TRACE', 'STACK_TRACE', 'HAS_TRACE' => $context->isException && !empty($tags['trace']),
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
}
