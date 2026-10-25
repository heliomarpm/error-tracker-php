<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Formatters;

use HeliomarPM\ErrorTracker\ErrorContext;

class TemplateRenderer
{
    /**
     * Renderiza um template substituindo tags no formato {{tag}} pelos valores do ErrorContext.
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
            'timestamp' => date('Y-m-d H:i:s'),
            'severity' => strtoupper($context->severity->name),
            'severity_value' => strtolower($context->severity->value),
            'message' => $context->message,
            'file' => $context->file,
            'line' => (string)$context->line,
            'trace' => $context->trace,
            'payload' => $payloadJson,
            'hash' => $context->hash,
            'code' => (string)$context->code,
        ], $extraTags);

        $replacements = [];
        foreach ($tags as $key => $value) {
            $replacements['{{' . $key . '}}'] = (string)($value ?? '');
        }

        return strtr($content, $replacements);
    }
}
