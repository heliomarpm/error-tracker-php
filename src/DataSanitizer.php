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
        'passwd', 'pwd', 'dsn', 'database_url', 'connection_string', 'conn_str',
        'db_pass', 'db_password', 'db_user', 'db_username', 'db_host', 'db_port', 'db_name',
        'smtp_pass', 'smtp_password', 'smtp_user', 'smtp_host',
        'redis_url', 'redis_password', 'api_key', 'apikey', 'private_key', 'access_key',
    ];

    /** @var string[] */
    private array $maskedKeys;

    private int $maxDepth;
    private bool $enabled;

    /**
     * @param string[] $additionalMaskedKeys Chaves adicionais que devem sofrer blackout total.
     * @param int $maxDepth Profundidade máxima de recursão em arrays aninhados (padrão 5).
     * @param bool $enabled Habilita ou desabilita o mascaramento de dados sensíveis e de conexão (padrão true).
     */
    public function __construct(
        array $additionalMaskedKeys = [],
        int $maxDepth = self::DEFAULT_MAX_DEPTH,
        bool $enabled = true
    ) {
        $this->maskedKeys = array_values(array_unique(array_merge(
            self::DEFAULT_MASKED_KEYS,
            array_map('strtolower', $additionalMaskedKeys)
        )));
        $this->maxDepth = max(1, $maxDepth);
        $this->enabled = $enabled;
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
     * Indica se o mascaramento de dados está habilitado.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Habilita ou desabilita o mascaramento de dados sensíveis e de conexão.
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Sanitiza mensagens de erro ou stack traces mascarando credenciais e strings de conexão.
     */
    public function sanitizeText(string $text): string
    {
        if (!$this->enabled || $text === '') {
            return $text;
        }

        return $this->maskConnectionData($text);
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
        if (!$this->enabled) {
            return $data;
        }

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

            // 2. Máscara inteligente baseada no padrão do valor (Conexão + Regex PII)
            if (is_string($value)) {
                $maskedValue = $this->maskConnectionData($value);
                $clean[$key] = $this->maskSensitivePatterns($maskedValue);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    private function maskConnectionData(string $text): string
    {
        if (strlen($text) > 65536) {
            return $text;
        }

        // 1. Argumentos de construtores/funções de conexão em stack traces e erros fatais
        $text = preg_replace(
            '/\b((?:PDO|mysqli|SmtpTransport|Redis|MongoDB\\\\Client)->(?:__construct|connect|auth)|mysqli_(?:real_)?connect|pg_p?connect|sqlsrv_connect|oci_(?:p|new_)?connect)\s*(\((?:[^()]+|(?2))*\))/i',
            '$1([CONNECTION_ARGS_MASKED])',
            $text
        ) ?? $text;

        // 2. URIs de conexão com credenciais (ex: mysql://user:pass@host:3306/db)
        $text = preg_replace(
            '/\b([a-z][a-z0-9+.-]*:\/\/)([^:@\/\s\'"]*:)?([^@\/\s\'"]+)@([^\s;,\'")]+)/i',
            '$1***:********@********',
            $text
        ) ?? $text;

        // 3. DSNs PDO / ODBC / SQL Server / Oracle / Postgres
        $text = preg_replace_callback(
            '/\b(mysql|pgsql|sqlsrv|oci|odbc|dblib|firebird|informix):([^\s\'"\)]+)/i',
            static function (array $matches): string {
                $body = preg_replace(
                    '/\b(host|server|port|dbname|database|user|username|uid|password|passwd|pwd|unix_socket)\s*=\s*[^;\s\'"\)]+/i',
                    '$1=********',
                    $matches[2]
                ) ?? $matches[2];
                return $matches[1] . ':' . $body;
            },
            $text
        ) ?? $text;

        // 4. Parâmetros chave=valor de conexão soltos
        $text = preg_replace(
            '/\b(password|passwd|pwd|dsn|host|server|dbname|database|uid|api_key|apikey|access_key|secret_key|private_key)\s*=\s*([^\s;,\'")]+)/i',
            '$1=********',
            $text
        ) ?? $text;

        // 5. Mensagens de erro de autenticação e conexão de bancos de dados
        $text = preg_replace(
            '/(Access denied for user\s+)([\'"`]?)[^\'"`@\s]+(\2)\s*@\s*([\'"`]?)[^\'"`\s)]+(\4)/i',
            '$1$2***$3@$4********$5',
            $text
        ) ?? $text;

        $text = preg_replace(
            '/(Unknown database\s+)([\'"`])[^\'"`]+(\2)/i',
            '$1$2********$3',
            $text
        ) ?? $text;

        $text = preg_replace(
            '/(authentication failed for user\s+)([\'"`])[^\'"`]+(\2)/i',
            '$1$2***$3',
            $text
        ) ?? $text;

        $text = preg_replace(
            '/(could not translate host name\s+)([\'"`])[^\'"`]+(\2)/i',
            '$1$2********$3',
            $text
        ) ?? $text;

        $text = preg_replace(
            '/(getaddrinfo for\s+)([^\s\'"`]+)(\s+failed)/i',
            '$1********$3',
            $text
        ) ?? $text;

        // 6. Cabeçalhos / tokens Bearer e Basic em texto
        $text = preg_replace(
            '/\b(Bearer|Basic)\s+[A-Za-z0-9\-._~+\/]+=*/i',
            '$1 ********',
            $text
        ) ?? $text;

        return $text;
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
