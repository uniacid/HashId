<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Service;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Service\HasherFactory;

/**
 * Edge case and boundary testing for HasherFactory.
 *
 * @covers \Pgs\HashIdBundle\Service\HasherFactory
 */
class HasherFactoryEdgeCaseTest extends TestCase
{
    /**
     * Test LRU cache eviction behavior.
     */
    public function testLRUCacheEviction(): void
    {
        // Cache size of 3 for testing
        $factory = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 3);

        // Create 4 different hashers (cache size = 3)
        $hasher1 = $factory->create('default', ['salt' => 'salt1']);
        $hasher2 = $factory->create('default', ['salt' => 'salt2']);
        $hasher3 = $factory->create('default', ['salt' => 'salt3']);

        // Access hasher1 to make it recently used
        $sameHasher1 = $factory->create('default', ['salt' => 'salt1']);
        $this->assertSame($hasher1, $sameHasher1);

        // Add a 4th hasher - should evict hasher2 (least recently used)
        $hasher4 = $factory->create('default', ['salt' => 'salt4']);

        // Verify hasher2 was evicted (should create new instance)
        $newHasher2 = $factory->create('default', ['salt' => 'salt2']);
        $this->assertNotSame($hasher2, $newHasher2);

        // Verify hasher1 and hasher3 are still cached
        $cachedHasher1 = $factory->create('default', ['salt' => 'salt1']);
        $cachedHasher3 = $factory->create('default', ['salt' => 'salt3']);
        $this->assertSame($hasher1, $cachedHasher1);
        $this->assertSame($hasher3, $cachedHasher3);
    }

    /**
     * Test cache statistics collection.
     */
    public function testCacheStatistics(): void
    {
        $factory = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 2);

        // Initial statistics
        $stats = $factory->getCacheStatistics();
        $this->assertEquals(0, $stats['cache_hits']);
        $this->assertEquals(0, $stats['cache_misses']);
        $this->assertEquals(0, $stats['cache_evictions']);
        $this->assertEquals(0.0, $stats['hit_rate_percentage']);

        // Create hasher (cache miss)
        $hasher1 = $factory->create('default', ['salt' => 'test1']);
        $stats = $factory->getCacheStatistics();
        $this->assertEquals(0, $stats['cache_hits']);
        $this->assertEquals(1, $stats['cache_misses']);

        // Access same hasher (cache hit)
        $sameHasher1 = $factory->create('default', ['salt' => 'test1']);
        $this->assertSame($hasher1, $sameHasher1);
        $stats = $factory->getCacheStatistics();
        $this->assertEquals(1, $stats['cache_hits']);
        $this->assertEquals(1, $stats['cache_misses']);
        $this->assertEquals(50.0, $stats['hit_rate_percentage']);

        // Fill cache and trigger eviction
        $factory->create('default', ['salt' => 'test2']);  // miss
        $factory->create('default', ['salt' => 'test3']);  // miss + eviction
        $stats = $factory->getCacheStatistics();
        $this->assertEquals(1, $stats['cache_evictions']);
        $this->assertEquals(1, $stats['cache_hits']);
        $this->assertEquals(3, $stats['cache_misses']);
    }

    /**
     * Test extreme alphabet configurations.
     */
    public function testExtremeAlphabetConfigurations(): void
    {
        // Test minimum valid alphabet (16 unique chars)
        $minAlphabet = 'abcdefghijklmnop';
        $factory = new HasherFactory(null, 10, $minAlphabet);
        $hasher = $factory->create('default');
        $this->assertNotNull($hasher);

        // Test very long alphabet with many duplicates
        $longAlphabet = str_repeat('abcdefghijklmnopqrstuvwxyz', 10);
        $factory2 = new HasherFactory(null, 10, $longAlphabet);
        $hasher2 = $factory2->create('default');
        $this->assertNotNull($hasher2);

        // Test alphabet with special characters
        $specialAlphabet = 'abcdefghijklmnop!@#$%^&*()_+-=[]{}|;:,.<>?';
        $factory3 = new HasherFactory(null, 10, $specialAlphabet);
        $hasher3 = $factory3->create('default');
        $this->assertNotNull($hasher3);
    }

    /**
     * Test malformed configuration handling.
     */
    public function testMalformedConfigurations(): void
    {
        $factory = new HasherFactory();

        // Test with alphabet having invalid length in config
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Alphabet must contain at least 16 unique characters');
        $factory->create('default', ['alphabet' => 'short']);
    }

    /**
     * Test unknown hasher type handling.
     */
    public function testUnknownHasherType(): void
    {
        $factory = new HasherFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown hasher type "invalid"');
        $factory->create('invalid');
    }

    /**
     * Test type injection prevention.
     */
    public function testTypeInjectionPrevention(): void
    {
        $factory = new HasherFactory();

        // Try to inject malicious type
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown hasher type');
        $factory->create('../../etc/passwd');
    }

    /**
     * Test boundary values for numeric configurations.
     */
    public function testBoundaryValues(): void
    {
        // Test zero min_length (should work)
        $factory1 = new HasherFactory(null, 0);
        $hasher1 = $factory1->create('default');
        $this->assertNotNull($hasher1);

        // Test very large min_length
        $factory2 = new HasherFactory(null, 1000);
        $hasher2 = $factory2->create('default');
        $this->assertNotNull($hasher2);

        // Test minimum cache size (1)
        $factory3 = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 1);
        $hasher3a = $factory3->create('default', ['salt' => 'a']);
        $hasher3b = $factory3->create('default', ['salt' => 'b']); // Should evict 3a
        $hasher3c = $factory3->create('default', ['salt' => 'a']); // Should be new (3a was evicted)

        $this->assertNotSame($hasher3a, $hasher3c);
    }

    /**
     * Test empty and null configuration values.
     */
    public function testEmptyConfigurationValues(): void
    {
        $factory = new HasherFactory();

        // Test empty salt (should work)
        $hasher1 = $factory->create('default', ['salt' => '']);
        $this->assertNotNull($hasher1);

        // Test zero min_length in config (should work)
        $hasher2 = $factory->create('default', ['min_length' => 0]);
        $this->assertNotNull($hasher2);

        // Test null values should use defaults
        $hasher3 = $factory->create('default', [
            'salt' => null,
            'min_length' => null,
            'alphabet' => null,
        ]);
        $this->assertNotNull($hasher3);
    }

    /**
     * Test cache clearing functionality.
     */
    public function testCacheClearingBehavior(): void
    {
        $factory = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 5);

        // Create some hashers
        $hasher1 = $factory->create('default', ['salt' => 'test1']);
        $hasher2 = $factory->create('default', ['salt' => 'test2']);

        // Verify cache is working
        $sameHasher1 = $factory->create('default', ['salt' => 'test1']);
        $this->assertSame($hasher1, $sameHasher1);

        // Clear cache
        $factory->clearInstanceCache();

        // Verify cache was cleared
        $newHasher1 = $factory->create('default', ['salt' => 'test1']);
        $this->assertNotSame($hasher1, $newHasher1);

        // Verify statistics were not cleared
        $stats = $factory->getCacheStatistics();
        $this->assertGreaterThan(0, $stats['cache_hits']);
    }

    /**
     * Test statistics reset functionality.
     */
    public function testStatisticsReset(): void
    {
        $factory = new HasherFactory();

        // Generate some cache activity
        $factory->create('default', ['salt' => 'test1']);
        $factory->create('default', ['salt' => 'test1']); // hit
        $factory->create('default', ['salt' => 'test2']);

        $stats = $factory->getCacheStatistics();
        $this->assertGreaterThan(0, $stats['cache_hits']);
        $this->assertGreaterThan(0, $stats['cache_misses']);

        // Reset statistics
        $factory->resetCacheStatistics();

        $stats = $factory->getCacheStatistics();
        $this->assertEquals(0, $stats['cache_hits']);
        $this->assertEquals(0, $stats['cache_misses']);
        $this->assertEquals(0, $stats['cache_evictions']);
        $this->assertEquals(0.0, $stats['hit_rate_percentage']);
    }

    /**
     * Test concurrent cache operations edge cases.
     */
    public function testConcurrentCacheOperations(): void
    {
        $factory = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 2);

        // Simulate rapid consecutive operations
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $factory->create('default', ['salt' => "concurrent_$i"]);
        }

        // Verify that we get consistent results for same config
        for ($i = 0; $i < 10; $i++) {
            $hasher = $factory->create('default', ['salt' => "concurrent_$i"]);
            // Due to cache size limit, only recent ones should be cached
            if ($i >= 8) { // Last 2 should be cached
                $this->assertSame($results[$i], $hasher);
            }
        }
    }

    /**
     * Test secure hasher special cases.
     */
    public function testSecureHasherEdgeCases(): void
    {
        $factory = new HasherFactory();

        // Test secure hasher with empty salt (should generate one)
        $hasher1 = $factory->create('secure', ['salt' => '']);
        $this->assertNotNull($hasher1);

        // Test secure hasher with null salt (should generate one)
        $hasher2 = $factory->create('secure', ['salt' => null]);
        $this->assertNotNull($hasher2);

        // Different instances should work independently
        $encoded1 = $hasher1->encode(123);
        $encoded2 = $hasher2->encode(123);
        $this->assertNotEmpty($encoded1);
        $this->assertNotEmpty($encoded2);
    }

    /**
     * Test preload configuration edge cases.
     */
    public function testPreloadConfigurationEdgeCases(): void
    {
        $factory = new HasherFactory();

        // Test preloading with invalid type
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown hasher type "invalid"');
        $factory->preloadConfiguration('invalid');
    }

    /**
     * Test cache usage percentage calculation.
     */
    public function testCacheUsagePercentage(): void
    {
        $factory = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 10);

        // Empty cache
        $stats = $factory->getCacheStatistics();
        $this->assertEquals(0.0, $stats['cache_usage_percentage']);

        // Half full cache
        for ($i = 0; $i < 5; $i++) {
            $factory->create('default', ['salt' => "test_$i"]);
        }
        $stats = $factory->getCacheStatistics();
        $this->assertEquals(50.0, $stats['cache_usage_percentage']);

        // Full cache
        for ($i = 5; $i < 10; $i++) {
            $factory->create('default', ['salt' => "test_$i"]);
        }
        $stats = $factory->getCacheStatistics();
        $this->assertEquals(100.0, $stats['cache_usage_percentage']);

        // Over capacity (should still report 100%)
        $factory->create('default', ['salt' => 'overflow']);
        $stats = $factory->getCacheStatistics();
        $this->assertEquals(100.0, $stats['cache_usage_percentage']);
    }
}