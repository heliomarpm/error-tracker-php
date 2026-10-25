<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Tests\Unit;

use HeliomarPM\ErrorTracker\Cache\FileCache;
use PHPUnit\Framework\TestCase;

final class FileCacheTest extends TestCase
{
    private string $tempDir;
    private FileCache $cache;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/error_tracker_test_' . uniqid();
        $this->cache = new FileCache($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        if (is_dir($this->tempDir)) {
            @rmdir($this->tempDir);
        }
    }

    public function testSetAndGet(): void
    {
        $this->assertTrue($this->cache->set('chave1', 'valor1', 60));
        $this->assertSame('valor1', $this->cache->get('chave1'));
        $this->assertTrue($this->cache->has('chave1'));
    }

    public function testReturnsDefaultWhenNotFound(): void
    {
        $this->assertNull($this->cache->get('inexistente'));
        $this->assertSame('default_val', $this->cache->get('inexistente', 'default_val'));
        $this->assertFalse($this->cache->has('inexistente'));
    }

    public function testDeletesKey(): void
    {
        $this->cache->set('del_key', 'some_data', 60);
        $this->assertTrue($this->cache->has('del_key'));

        $this->assertTrue($this->cache->delete('del_key'));
        $this->assertFalse($this->cache->has('del_key'));
    }

    public function testClearsAllEntries(): void
    {
        $this->cache->set('k1', 'v1', 60);
        $this->cache->set('k2', 'v2', 60);

        $this->assertTrue($this->cache->clear());
        $this->assertFalse($this->cache->has('k1'));
        $this->assertFalse($this->cache->has('k2'));
    }

    public function testMultipleOperations(): void
    {
        $values = [
            'batch_1' => 'alpha',
            'batch_2' => 'beta',
            'batch_3' => 'gamma',
        ];

        $this->assertTrue($this->cache->setMultiple($values, 60));

        $retrieved = $this->cache->getMultiple(['batch_1', 'batch_2', 'batch_4'], 'fallback');
        $this->assertSame([
            'batch_1' => 'alpha',
            'batch_2' => 'beta',
            'batch_4' => 'fallback',
        ], $retrieved);

        $this->assertTrue($this->cache->deleteMultiple(['batch_1', 'batch_2']));
        $this->assertFalse($this->cache->has('batch_1'));
        $this->assertFalse($this->cache->has('batch_2'));
        $this->assertTrue($this->cache->has('batch_3'));
    }

    public function testExpiresWhenTtlIsExceeded(): void
    {
        // 0 ou negativo expira imediatamente
        $this->cache->set('expired', 'old_data', -5);
        $this->assertNull($this->cache->get('expired'));
        $this->assertFalse($this->cache->has('expired'));
    }
}
