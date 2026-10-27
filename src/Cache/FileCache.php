<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Cache;

use DateInterval;
use DateTime;
use Psr\SimpleCache\CacheInterface;

/**
 * Implementação padrão de cache PSR-16 em disco para anti-flood do ErrorTracker.
 *
 * @internal Esta implementação é de uso padrão interno; para customizações, forneça uma instância externa de CacheInterface.
 */
final class FileCache implements CacheInterface
{
    private string $cacheDirectory;

    public function __construct(?string $cacheDirectory = null)
    {
        $this->cacheDirectory = rtrim($cacheDirectory ?? sys_get_temp_dir() . '/ErrorTracker_Cache', '/\\');

        // Garante que o diretório de cache exista
        if (!is_dir($this->cacheDirectory)) {
            mkdir($this->cacheDirectory, 0777, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        // Verifica se o cache expirou
        if ($data['expires_at'] !== null && time() > $data['expires_at']) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $file = $this->getFilePath($key);

        $expiresAt = null;
        if ($ttl !== null) {
            $seconds = $ttl instanceof DateInterval ? (new DateTime('@0'))->add($ttl)->getTimestamp() : $ttl;
            $expiresAt = time() + $seconds;
        }

        $data = [
            'value' => $value,
            'expires_at' => $expiresAt,
        ];

        // Usamos LOCK_EX para evitar condição de corrida caso múltiplas requisições do PDV falhem no exato mesmo milissegundo
        return file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->cacheDirectory . '/*');
        $success = true;
        foreach ($files as $file) {
            if (is_file($file)) {
                $success = $success && unlink($file);
            }
        }
        return $success;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get((string)$key, $default);
        }

        return $result;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            $success = $this->set((string)$key, $value, $ttl) && $success;
        }

        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            $success = $this->delete((string)$key) && $success;
        }

        return $success;
    }

    private function getFilePath(string $key): string
    {
        // Sanitiza a chave usando MD5 para evitar problemas com caracteres especiais no sistema de arquivos do S.O.
        return $this->cacheDirectory . '/' . md5($key) . '.cache';
    }
}
