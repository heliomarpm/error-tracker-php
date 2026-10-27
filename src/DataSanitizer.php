<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker;

/**
 * Sanitizador de dados sensíveis para conformidade com LGPD/GDPR.
 *
 * @internal Esta classe é de uso exclusivo interno do ErrorTracker e pode sofrer alterações sem aviso prévio.
 */
final class DataSanitizer
{
    public const DEFAULT_MAX_DEPTH = 5;

    // Chaves padrão que sofrem "Blackout" total
    private const DEFAULT_MASKED_KEYS = [
        'password', 'senha', 'token', 'authorization', 'secret', 'cvv',
    ];

    /** @var string[] */
    private array $maskedKeys;

    private int $maxDepth;

    /**
     * @param string[] $additionalMaskedKeys Chaves adicionais que devem sofrer blackout total.
     * @param int $maxDepth Profundidade máxima de recursão em arrays aninhados (padrão 5).
     */
    public function __construct(
        array $additionalMaskedKeys = [],
        int $maxDepth = self::DEFAULT_MAX_DEPTH
    ) {
        $this->maskedKeys = array_values(array_unique(array_merge(
            self::DEFAULT_MASKED_KEYS,
            array_map('strtolower', $additionalMaskedKeys)
        )));
        $this->maxDepth = max(1, $maxDepth);
    }

    /**
     * Adiciona chaves adicionais para sofrerem blackout total.
     *
     * @param string[] $keys
     */
    public function addMaskedKeys(array $keys): self
    {
        $lowered = array_map('strtolower', $keys);
        $this->maskedKeys = array_values(array_unique(array_merge($this->maskedKeys, $lowered)));

        return $this;
    }

    /**
     * Retorna a lista atual de chaves configuradas para blackout total.
     *
     * @return string[]
     */
    public function getMaskedKeys(): array
    {
        return $this->maskedKeys;
    }

    /**
     * Retorna a profundidade máxima permitida.
     */
    public function getMaxDepth(): int
    {
        return $this->maxDepth;
    }

    /**
     * Define uma nova profundidade máxima de recursão.
     */
    public function setMaxDepth(int $maxDepth): self
    {
        $this->maxDepth = max(1, $maxDepth);

        return $this;
    }

    /**
     * Sanitiza os dados mascarando dados confidenciais e limitando a profundidade de recursão.
     *
     * @param array<string|int, mixed> $data
     * @param int $depth
     * @return array<string|int, mixed>
     */
    public function sanitize(array $data, int $depth = 0): array
    {
        if ($depth >= $this->maxDepth) {
            return ['[DEPTH_LIMIT]' => '[DEPTH_LIMIT]'];
        }

        $clean = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($depth + 1 >= $this->maxDepth) {
                    $clean[$key] = '[DEPTH_LIMIT]';
                } else {
                    $clean[$key] = $this->sanitize($value, $depth + 1);
                }
                continue;
            }

            // 1. Blackout total para chaves de alto risco
            if ($this->isFullyMaskedKey((string)$key)) {
                $clean[$key] = '********';
                continue;
            }

            // 2. Máscara inteligente baseada no padrão do valor (Regex)
            if (is_string($value)) {
                $clean[$key] = $this->maskSensitivePatterns($value);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    private function isFullyMaskedKey(string $key): bool
    {
        $keyLower = strtolower($key);
        foreach ($this->maskedKeys as $sensitive) {
            if ($sensitive !== '' && str_contains($keyLower, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    private function maskSensitivePatterns(string $value): string
    {
        // Se a string for excessivamente longa (> 500 caracteres), evita regex pesados
        // pois padrões sensíveis (CPF, e-mail, cartão, telefone) têm comprimento delimitado.
        if (strlen($value) > 500) {
            return $value;
        }

        // Máscara de E-mail (ex: heliomarpm@gmail.com -> hel***@gmail.com)
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $parts = explode('@', $value);
            $name = $parts[0];
            // Se o nome for muito curto, mascara tudo. Se não, mostra os 3 primeiros caracteres.
            $visibleChars = strlen($name) > 3 ? 3 : 1;
            $maskedName = substr($name, 0, $visibleChars) . str_repeat('*', strlen($name) - $visibleChars);

            return $maskedName . '@' . $parts[1];
        }

        // Máscara de CPF (ex: 123.456.789-10 -> ***.***.789-10)
        // Cobre os 6 primeiros dígitos para proteger a origem, mas permite identificação no final
        $value = (string)preg_replace('/(\d{3})\.(\d{3})\.(\d{3})-(\d{2})/', '***.***.$3-$4', $value);

        // Máscara de Cartão de Crédito (ex: 1234 5678 1234 5678 -> **** **** **** 5678)
        $value = (string)preg_replace('/(?:\d{4}[ -]?){3}(\d{4})/', '**** **** **** $1', $value);

        // Máscara de Telefone BR (ex: (11) 98765-4321 -> (11) 9****-4321)
        $value = (string)preg_replace('/(\(\d{2}\)\s?9?)\d{4}-(\d{4})/', '$1****-$2', $value);

        return $value;
    }
}
