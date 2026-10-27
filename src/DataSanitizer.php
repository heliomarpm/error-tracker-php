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
    // Chaves que sofrem "Blackout" total
    private array $fullyMaskedKeys = [
        'password', 'senha', 'token', 'authorization', 'secret', 'cvv',
    ];

    public function sanitize(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = $this->sanitize($value);
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
        foreach ($this->fullyMaskedKeys as $sensitive) {
            if (str_contains($keyLower, $sensitive)) {
                return true;
            }
        }
        return false;
    }

    private function maskSensitivePatterns(string $value): string
    {
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
        $value = preg_replace('/(\d{3})\.(\d{3})\.(\d{3})-(\d{2})/', '***.***.$3-$4', $value);

        // Máscara de Cartão de Crédito (ex: 1234 5678 1234 5678 -> **** **** **** 5678)
        $value = preg_replace('/(?:\d{4}[ -]?){3}(\d{4})/', '**** **** **** $1', $value);

        // Máscara de Telefone BR (ex: (11) 98765-4321 -> (11) 9****-4321)
        $value = preg_replace('/(\(\d{2}\)\s?9?)\d{4}-(\d{4})/', '$1****-$2', $value);

        return $value;
    }
}
