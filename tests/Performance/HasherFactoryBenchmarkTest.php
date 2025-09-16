<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Performance;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Service\HasherFactory;
use Pgs\HashIdBundle\Tests\Performance\Framework\BenchmarkRunner;

/**
 * Performance benchmarks for HasherFactory operations.
 *
 * @group performance
 * @group benchmark
 * @group hasher-factory
 */
class HasherFactoryBenchmarkTest extends TestCase
{
    private BenchmarkRunner $runner;

    protected function setUp(): void
    {
        $this->runner = new BenchmarkRunner(1000, 100);
        $this->runner->setVerbose(getenv('VERBOSE') === '1');
    }

    /**
     * Benchmark hasher creation performance.
     */
    public function testHasherCreationPerformance(): void
    {
        $factory = new HasherFactory();

        $result = $this->runner->benchmark('hasher_creation', function () use ($factory) {
            $factory->create('default', ['salt' => 'benchmark_test']);
        });

        // Should create hashers in under 0.1ms
        $this->assertLessThan(0.1, $result->getMean(),
            sprintf('Hasher creation too slow: %.4fms', $result->getMean()));

        if (getenv('CI')) {
            echo sprintf(
                "\nHasher Creation Performance:\n" .
                "  Mean: %.4fms\n" .
                "  Median: %.4fms\n" .
                "  95th percentile: %.4fms\n" .
                "  Ops/sec: %.0f\n",
                $result->getMean(),
                $result->getMedian(),
                $result->getPercentile95(),
                $result->getOpsPerSecond()
            );
        }
    }

    /**
     * Benchmark cache hit vs miss performance.
     */
    public function testCacheHitVsMissPerformance(): void
    {
        $factory = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 100);

        // Pre-populate cache
        $config = ['salt' => 'cached_test'];
        $factory->create('default', $config);

        $cacheHitResult = $this->runner->benchmark('cache_hit', function () use ($factory, $config) {
            $factory->create('default', $config);
        });

        $cacheMissResult = $this->runner->benchmark('cache_miss', function () use ($factory) {
            $factory->create('default', ['salt' => 'uncached_' . random_int(1, 100000)]);
        });

        // Cache hits should be significantly faster than misses
        $speedup = $cacheMissResult->getMean() / $cacheHitResult->getMean();
        $this->assertGreaterThan(2, $speedup,
            'Cache hits should be at least 2x faster than misses');

        if (getenv('CI')) {
            echo sprintf(
                "\nCache Performance Comparison:\n" .
                "  Cache Hit: %.4fms\n" .
                "  Cache Miss: %.4fms\n" .
                "  Speedup: %.2fx\n",
                $cacheHitResult->getMean(),
                $cacheMissResult->getMean(),
                $speedup
            );
        }
    }

    /**
     * Benchmark different hasher types performance.
     */
    public function testHasherTypePerformance(): void
    {
        $factory = new HasherFactory();

        $defaultResult = $this->runner->benchmark('default_hasher', function () use ($factory) {
            $factory->create('default', ['salt' => 'test_default']);
        });

        $secureResult = $this->runner->benchmark('secure_hasher', function () use ($factory) {
            $factory->create('secure', ['salt' => 'test_secure']);
        });

        $customResult = $this->runner->benchmark('custom_hasher', function () use ($factory) {
            $factory->create('custom', ['salt' => 'test_custom']);
        });

        // All should be reasonably fast (under 0.5ms)
        $this->assertLessThan(0.5, $defaultResult->getMean());
        $this->assertLessThan(0.5, $secureResult->getMean());
        $this->assertLessThan(0.5, $customResult->getMean());

        if (getenv('CI')) {
            echo sprintf(
                "\nHasher Type Performance:\n" .
                "  Default: %.4fms\n" .
                "  Secure: %.4fms\n" .
                "  Custom: %.4fms\n",
                $defaultResult->getMean(),
                $secureResult->getMean(),
                $customResult->getMean()
            );
        }
    }

    /**
     * Benchmark cache eviction performance.
     */
    public function testCacheEvictionPerformance(): void
    {
        // Small cache to force frequent evictions
        $factory = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 10);

        $result = $this->runner->benchmark('cache_eviction', function () use ($factory) {
            // Create hashers that will force evictions
            for ($i = 0; $i < 15; $i++) {
                $factory->create('default', ['salt' => "eviction_test_$i"]);
            }
        });

        // Should handle evictions efficiently (under 2ms for 15 creations)
        $this->assertLessThan(2, $result->getMean(),
            sprintf('Cache eviction too slow: %.4fms', $result->getMean()));

        $stats = $factory->getCacheStatistics();
        $this->assertGreaterThan(0, $stats['cache_evictions'],
            'Should have triggered cache evictions');

        if (getenv('CI')) {
            echo sprintf(
                "\nCache Eviction Performance:\n" .
                "  Mean time (15 operations): %.4fms\n" .
                "  Per operation: %.4fms\n" .
                "  Evictions triggered: %d\n",
                $result->getMean(),
                $result->getMean() / 15,
                $stats['cache_evictions']
            );
        }
    }

    /**
     * Benchmark memory usage under load.
     */
    public function testMemoryUsageUnderLoad(): void
    {
        $factory = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 50);

        $memoryProfile = $this->runner->profileMemory(function () use ($factory) {
            // Create many different hashers
            for ($i = 0; $i < 200; $i++) {
                $factory->create('default', ['salt' => "memory_test_$i", 'min_length' => $i % 30]);
            }
        }, 5);

        // Should use reasonable memory (less than 10MB for 1000 creations)
        $this->assertLessThan(
            10 * 1024 * 1024,
            $memoryProfile['used'],
            sprintf('Memory usage too high: %s', $this->formatBytes($memoryProfile['used']))
        );

        if (getenv('CI')) {
            echo sprintf(
                "\nMemory Usage (1000 operations):\n" .
                "  Total memory used: %s\n" .
                "  Per operation: %s\n" .
                "  Peak memory: %s\n",
                $this->formatBytes($memoryProfile['used']),
                $this->formatBytes($memoryProfile['used'] / 1000),
                $this->formatBytes($memoryProfile['peak'])
            );
        }
    }

    /**
     * Benchmark complex configuration performance.
     */
    public function testComplexConfigurationPerformance(): void
    {
        $factory = new HasherFactory();

        $complexConfig = [
            'salt' => 'very-complex-salt-with-many-characters-12345678',
            'min_length' => 50,
            'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?',
        ];

        $result = $this->runner->benchmark('complex_config', function () use ($factory, $complexConfig) {
            $factory->create('default', $complexConfig);
        });

        // Complex configs should still be fast (under 0.2ms)
        $this->assertLessThan(0.2, $result->getMean(),
            sprintf('Complex config handling too slow: %.4fms', $result->getMean()));

        if (getenv('CI')) {
            echo sprintf(
                "\nComplex Configuration Performance:\n" .
                "  Mean: %.4fms\n" .
                "  Config complexity: %d keys, %d char alphabet\n" .
                "  Ops/sec: %.0f\n",
                $result->getMean(),
                count($complexConfig),
                strlen($complexConfig['alphabet']),
                $result->getOpsPerSecond()
            );
        }
    }

    /**
     * Benchmark concurrent-like access patterns.
     */
    public function testConcurrentAccessPerformance(): void
    {
        $factory = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 20);

        // Simulate concurrent access patterns
        $configs = [];
        for ($i = 0; $i < 50; $i++) {
            $configs[] = ['salt' => "concurrent_$i", 'min_length' => $i % 20];
        }

        $result = $this->runner->benchmark('concurrent_access', function () use ($factory, $configs) {
            // Multiple accesses to same configs (simulating concurrent users)
            foreach ($configs as $config) {
                $factory->create('default', $config);
            }
            // Access again (should be cache hits)
            foreach ($configs as $config) {
                $factory->create('default', $config);
            }
        });

        // Should handle concurrent-like patterns efficiently
        $this->assertLessThan(10, $result->getMean(),
            sprintf('Concurrent access too slow: %.4fms', $result->getMean()));

        $stats = $factory->getCacheStatistics();
        $this->assertGreaterThan(70, $stats['hit_rate_percentage'],
            'Should achieve good cache hit rate in concurrent scenario');

        if (getenv('CI')) {
            echo sprintf(
                "\nConcurrent Access Performance:\n" .
                "  Total time (100 operations): %.4fms\n" .
                "  Per operation: %.4fms\n" .
                "  Cache hit rate: %.2f%%\n",
                $result->getMean(),
                $result->getMean() / 100,
                $stats['hit_rate_percentage']
            );
        }
    }

    /**
     * Benchmark cache statistics performance.
     */
    public function testCacheStatisticsPerformance(): void
    {
        $factory = new HasherFactory();

        // Generate some cache activity
        for ($i = 0; $i < 20; $i++) {
            $factory->create('default', ['salt' => "stats_test_$i"]);
        }

        $result = $this->runner->benchmark('cache_statistics', function () use ($factory) {
            $factory->getCacheStatistics();
        });

        // Statistics should be very fast (under 0.01ms)
        $this->assertLessThan(0.01, $result->getMean(),
            sprintf('Cache statistics too slow: %.4fms', $result->getMean()));

        if (getenv('CI')) {
            echo sprintf(
                "\nCache Statistics Performance:\n" .
                "  Mean: %.4fms\n" .
                "  Ops/sec: %.0f\n",
                $result->getMean(),
                $result->getOpsPerSecond()
            );
        }
    }

    /**
     * Test performance regression detection.
     */
    public function testPerformanceRegression(): void
    {
        $factory = new HasherFactory();

        // Baseline performance
        $baseline = $this->runner->benchmark('baseline', function () use ($factory) {
            $factory->create('default', ['salt' => 'regression_test']);
        }, 500); // More iterations for stable baseline

        // Performance after cache activity
        for ($i = 0; $i < 100; $i++) {
            $factory->create('default', ['salt' => "activity_$i"]);
        }

        $afterActivity = $this->runner->benchmark('after_activity', function () use ($factory) {
            $factory->create('default', ['salt' => 'regression_test']);
        }, 500);

        // Performance should not degrade significantly (within 20%)
        $degradation = ($afterActivity->getMean() / $baseline->getMean()) - 1;
        $this->assertLessThan(0.2, $degradation,
            sprintf('Performance degraded by %.1f%%', $degradation * 100));

        if (getenv('CI')) {
            echo sprintf(
                "\nPerformance Regression Test:\n" .
                "  Baseline: %.4fms\n" .
                "  After activity: %.4fms\n" .
                "  Performance change: %+.1f%%\n",
                $baseline->getMean(),
                $afterActivity->getMean(),
                $degradation * 100
            );
        }
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $value = abs($bytes);

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        return sprintf('%.2f %s', $value, $units[$unitIndex]);
    }
}