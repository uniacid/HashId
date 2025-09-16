<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Service\HasherFactory;

/**
 * Comprehensive cache behavior testing for HasherFactory.
 *
 * @covers \Pgs\HashIdBundle\Service\HasherFactory
 */
class HasherFactoryCacheTest extends TestCase
{
    /**
     * Test cache key collision handling.
     */
    public function testCacheKeyCollisionHandling(): void
    {
        $factory = new HasherFactory();

        // Create configs that might generate similar cache keys
        $hasher1 = $factory->create('default', ['salt' => 'abc', 'min_length' => 10]);
        $hasher2 = $factory->create('default', ['salt' => 'abd', 'min_length' => 10]);
        $hasher3 = $factory->create('default', ['min_length' => 10, 'salt' => 'abc']); // Different order

        // Should cache correctly despite similar configs
        $this->assertNotSame($hasher1, $hasher2);
        $this->assertSame($hasher1, $hasher3); // Same config, different order
    }

    /**
     * Test cache behavior with complex configurations.
     */
    public function testComplexConfigurationCaching(): void
    {
        $factory = new HasherFactory();

        $complexConfig = [
            'salt' => 'complex-salt-12345',
            'min_length' => 25,
            'alphabet' => 'abcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*',
        ];

        $hasher1 = $factory->create('default', $complexConfig);
        $hasher2 = $factory->create('default', $complexConfig);

        $this->assertSame($hasher1, $hasher2);

        // Verify caching works with statistics
        $stats = $factory->getCacheStatistics();
        $this->assertEquals(1, $stats['cache_hits']);
        $this->assertEquals(1, $stats['cache_misses']);
    }

    /**
     * Test cache performance under load.
     */
    public function testCachePerformanceUnderLoad(): void
    {
        $factory = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 20);

        $startTime = microtime(true);

        // Create many hashers to test cache efficiency
        $configurations = [];
        for ($i = 0; $i < 100; $i++) {
            $config = ['salt' => "load_test_$i", 'min_length' => $i % 20];
            $configurations[] = $config;
            $factory->create('default', $config);
        }

        // Access existing configurations (should be cache hits)
        foreach ($configurations as $config) {
            $factory->create('default', $config);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete quickly (under 1 second for this load)
        $this->assertLessThan(1.0, $duration);

        $stats = $factory->getCacheStatistics();
        $this->assertGreaterThan(0, $stats['cache_hits']);
        $this->assertGreaterThan(50.0, $stats['hit_rate_percentage']);
    }

    /**
     * Test cache eviction patterns.
     */
    public function testCacheEvictionPatterns(): void
    {
        $factory = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 3);

        // Fill cache completely
        $hasher1 = $factory->create('default', ['salt' => '1']);
        $hasher2 = $factory->create('default', ['salt' => '2']);
        $hasher3 = $factory->create('default', ['salt' => '3']);

        // Access pattern: 1, 2, 1, 3 (making 2 least recently used)
        $factory->create('default', ['salt' => '1']); // hit
        $factory->create('default', ['salt' => '2']); // hit
        $factory->create('default', ['salt' => '1']); // hit
        $factory->create('default', ['salt' => '3']); // hit

        // Add new item - should evict '2' (least recently used)
        $hasher4 = $factory->create('default', ['salt' => '4']);

        // Verify eviction
        $newHasher2 = $factory->create('default', ['salt' => '2']);
        $this->assertNotSame($hasher2, $newHasher2);

        // Verify others are still cached
        $cachedHasher1 = $factory->create('default', ['salt' => '1']);
        $cachedHasher3 = $factory->create('default', ['salt' => '3']);
        $this->assertSame($hasher1, $cachedHasher1);
        $this->assertSame($hasher3, $cachedHasher3);

        $stats = $factory->getCacheStatistics();
        $this->assertEquals(1, $stats['cache_evictions']);
    }

    /**
     * Test cache behavior with different hasher types.
     */
    public function testMultipleHasherTypesCaching(): void
    {
        $factory = new HasherFactory();

        // Create hashers of different types with same config
        $config = ['salt' => 'shared'];
        $defaultHasher = $factory->create('default', $config);
        $secureHasher = $factory->create('secure', $config);
        $customHasher = $factory->create('custom', $config);

        // Should all be different instances (different types)
        $this->assertNotSame($defaultHasher, $secureHasher);
        $this->assertNotSame($defaultHasher, $customHasher);
        $this->assertNotSame($secureHasher, $customHasher);

        // But same type + config should be cached
        $sameDefaultHasher = $factory->create('default', $config);
        $this->assertSame($defaultHasher, $sameDefaultHasher);
    }

    /**
     * Test cache statistics accuracy.
     */
    public function testCacheStatisticsAccuracy(): void
    {
        $factory = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 5);

        // Known sequence of operations
        $factory->create('default', ['salt' => 'a']); // miss
        $factory->create('default', ['salt' => 'a']); // hit
        $factory->create('default', ['salt' => 'b']); // miss
        $factory->create('default', ['salt' => 'c']); // miss
        $factory->create('default', ['salt' => 'a']); // hit
        $factory->create('default', ['salt' => 'b']); // hit

        $stats = $factory->getCacheStatistics();
        $this->assertEquals(3, $stats['cache_hits']);
        $this->assertEquals(3, $stats['cache_misses']);
        $this->assertEquals(50.0, $stats['hit_rate_percentage']);
        $this->assertEquals(3, $stats['current_cache_size']);
        $this->assertEquals(5, $stats['max_cache_size']);
        $this->assertEquals(60.0, $stats['cache_usage_percentage']);
        $this->assertEquals(0, $stats['cache_evictions']);
    }

    /**
     * Test cache with empty configurations.
     */
    public function testCacheWithEmptyConfigurations(): void
    {
        $factory = new HasherFactory();

        // Empty config should be cached
        $hasher1 = $factory->create('default', []);
        $hasher2 = $factory->create('default', []);
        $this->assertSame($hasher1, $hasher2);

        // Different empty-like configs
        $hasher3 = $factory->create('default', ['salt' => null]);
        $hasher4 = $factory->create('default', ['salt' => '']);

        // These should be different cached instances
        $this->assertNotSame($hasher1, $hasher3);
        $this->assertNotSame($hasher1, $hasher4);
        $this->assertNotSame($hasher3, $hasher4);
    }

    /**
     * Test cache key sorting consistency.
     */
    public function testCacheKeySortingConsistency(): void
    {
        $factory = new HasherFactory();

        // Same config in different orders should cache to same instance
        $config1 = ['salt' => 'test', 'min_length' => 15, 'alphabet' => 'abcdefghijklmnopqrstuvwxyz'];
        $config2 = ['alphabet' => 'abcdefghijklmnopqrstuvwxyz', 'salt' => 'test', 'min_length' => 15];
        $config3 = ['min_length' => 15, 'alphabet' => 'abcdefghijklmnopqrstuvwxyz', 'salt' => 'test'];

        $hasher1 = $factory->create('default', $config1);
        $hasher2 = $factory->create('default', $config2);
        $hasher3 = $factory->create('default', $config3);

        $this->assertSame($hasher1, $hasher2);
        $this->assertSame($hasher1, $hasher3);

        $stats = $factory->getCacheStatistics();
        $this->assertEquals(2, $stats['cache_hits']);
        $this->assertEquals(1, $stats['cache_misses']);
    }

    /**
     * Test cache memory efficiency.
     */
    public function testCacheMemoryEfficiency(): void
    {
        $factory = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 100);

        $initialMemory = memory_get_usage(true);

        // Create many hashers
        for ($i = 0; $i < 50; $i++) {
            $factory->create('default', ['salt' => "memory_test_$i"]);
        }

        $afterCreationMemory = memory_get_usage(true);
        $memoryIncrease = $afterCreationMemory - $initialMemory;

        // Clear cache
        $factory->clearInstanceCache();

        $afterClearMemory = memory_get_usage(true);
        $memoryAfterClear = $afterClearMemory - $initialMemory;

        // Memory usage should be reasonable and clearing should help
        $this->assertLessThan(10 * 1024 * 1024, $memoryIncrease); // Less than 10MB
        $this->assertLessThan($memoryIncrease, $memoryAfterClear * 2); // Clearing should free significant memory
    }

    /**
     * Test cache behavior at exactly the size limit.
     */
    public function testCacheSizeLimitBehavior(): void
    {
        $factory = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 5);

        $hashers = [];

        // Fill cache to exactly the limit
        for ($i = 1; $i <= 5; $i++) {
            $hashers[$i] = $factory->create('default', ['salt' => "limit_$i"]);
        }

        $stats = $factory->getCacheStatistics();
        $this->assertEquals(5, $stats['current_cache_size']);
        $this->assertEquals(0, $stats['cache_evictions']);

        // Add one more - should trigger exactly one eviction
        $hashers[6] = $factory->create('default', ['salt' => 'limit_6']);

        $stats = $factory->getCacheStatistics();
        $this->assertEquals(5, $stats['current_cache_size']);
        $this->assertEquals(1, $stats['cache_evictions']);

        // Verify the first one was evicted (LRU)
        $newHasher1 = $factory->create('default', ['salt' => 'limit_1']);
        $this->assertNotSame($hashers[1], $newHasher1);

        // Verify others are still cached
        for ($i = 2; $i <= 6; $i++) {
            $cachedHasher = $factory->create('default', ['salt' => "limit_$i"]);
            if ($i <= 5) {
                $this->assertSame($hashers[$i], $cachedHasher, "Hasher $i should be cached");
            }
        }
    }

    /**
     * Test cache behavior with configuration inheritance.
     */
    public function testConfigurationInheritanceCaching(): void
    {
        $factory = new HasherFactory('base-salt', 20, 'abcdefghijklmnopqrstuvwxyz');

        // Config that overrides some defaults
        $hasher1 = $factory->create('default', ['min_length' => 30]);

        // Config that overrides different defaults
        $hasher2 = $factory->create('default', ['salt' => 'override-salt']);

        // Complete override
        $hasher3 = $factory->create('default', [
            'salt' => 'completely-new',
            'min_length' => 40,
            'alphabet' => 'abcdefghijklmnop'
        ]);

        // All should be different and properly cached
        $this->assertNotSame($hasher1, $hasher2);
        $this->assertNotSame($hasher1, $hasher3);
        $this->assertNotSame($hasher2, $hasher3);

        // Verify caching works for each
        $sameHasher1 = $factory->create('default', ['min_length' => 30]);
        $sameHasher2 = $factory->create('default', ['salt' => 'override-salt']);

        $this->assertSame($hasher1, $sameHasher1);
        $this->assertSame($hasher2, $sameHasher2);
    }
}