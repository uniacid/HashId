<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Performance;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Tests\Performance\Framework\BenchmarkRunner;
use Pgs\HashIdBundle\Service\HasherFactory;
use Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter;
use Hashids\Hashids;

/**
 * Performance benchmarks for encoding/decoding operations.
 *
 * @group performance
 * @group benchmark
 */
class EncodingBenchmarkTest extends TestCase
{
    private BenchmarkRunner $runner;
    private HasherFactory $factory;

    protected function setUp(): void
    {
        $this->runner = new BenchmarkRunner(1000, 100);
        $this->runner->setVerbose(getenv('VERBOSE') === '1');

        $this->factory = new HasherFactory('test-salt', 10, 'abcdefghijklmnopqrstuvwxyz');
    }

    /**
     * Benchmark basic encoding operations.
     */
    public function testEncodingPerformance(): void
    {
        $hasher = new HashidsConverter(new Hashids('test-salt', 10));

        $result = $this->runner->benchmark('encode_single_id', function () use ($hasher) {
            $hasher->encode(12345);
        });

        // Should encode in under 0.1ms
        $this->assertLessThan(0.1, $result->getMean(),
            sprintf('Encoding too slow: %.4fms', $result->getMean()));

        // Log performance for CI
        if (getenv('CI')) {
            echo sprintf(
                "\nEncoding Performance:\n" .
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
     * Benchmark basic decoding operations.
     */
    public function testDecodingPerformance(): void
    {
        $hasher = new HashidsConverter(new Hashids('test-salt', 10));
        $encoded = $hasher->encode(12345);

        $result = $this->runner->benchmark('decode_single_id', function () use ($hasher, $encoded) {
            $hasher->decode($encoded);
        });

        // Should decode in under 0.1ms
        $this->assertLessThan(0.1, $result->getMean(),
            sprintf('Decoding too slow: %.4fms', $result->getMean()));

        if (getenv('CI')) {
            echo sprintf(
                "\nDecoding Performance:\n" .
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
     * Benchmark encoding with different ID sizes.
     */
    public function testEncodingScalability(): void
    {
        $hasher = new HashidsConverter(new Hashids('test-salt', 10));

        $scenarios = [
            'small_id' => function () use ($hasher) {
                $hasher->encode(1);
            },
            'medium_id' => function () use ($hasher) {
                $hasher->encode(123456);
            },
            'large_id' => function () use ($hasher) {
                $hasher->encode(999999999);
            },
            'very_large_id' => function () use ($hasher) {
                $hasher->encode(PHP_INT_MAX);
            },
        ];

        $results = $this->runner->suite('encoding_scalability', $scenarios);

        // All should complete in under 0.2ms
        foreach ($results as $name => $result) {
            $this->assertLessThan(0.2, $result->getMean(),
                sprintf('%s encoding too slow: %.4fms', $name, $result->getMean()));
        }

        if (getenv('CI')) {
            echo "\nEncoding Scalability:\n";
            foreach ($results as $name => $result) {
                echo sprintf("  %s: %.4fms (%.0f ops/sec)\n",
                    $name,
                    $result->getMean(),
                    $result->getOpsPerSecond()
                );
            }
        }
    }

    /**
     * Benchmark batch encoding operations.
     */
    public function testBatchEncodingPerformance(): void
    {
        $hasher = new HashidsConverter(new Hashids('test-salt', 10));

        // Prepare batch of IDs
        $ids = range(1, 100);

        $result = $this->runner->benchmark('batch_encode_100', function () use ($hasher, $ids) {
            foreach ($ids as $id) {
                $hasher->encode($id);
            }
        });

        // Should process 100 IDs in under 10ms
        $this->assertLessThan(10, $result->getMean(),
            sprintf('Batch encoding too slow: %.4fms for 100 IDs', $result->getMean()));

        $perItemMs = $result->getMean() / 100;

        if (getenv('CI')) {
            echo sprintf(
                "\nBatch Encoding Performance (100 IDs):\n" .
                "  Total: %.4fms\n" .
                "  Per item: %.4fms\n" .
                "  Throughput: %.0f IDs/sec\n",
                $result->getMean(),
                $perItemMs,
                (1000 / $result->getMean()) * 100
            );
        }
    }

    /**
     * Compare different hasher configurations.
     */
    public function testHasherConfigurationImpact(): void
    {
        $configs = [
            'minimal' => ['salt' => 's', 'min_length' => 1],
            'default' => ['salt' => 'test-salt', 'min_length' => 10],
            'secure' => ['salt' => 'very-long-and-secure-salt-string', 'min_length' => 20],
            'complex' => ['salt' => str_repeat('x', 100), 'min_length' => 30],
        ];

        $results = [];
        foreach ($configs as $name => $config) {
            $hasher = new HashidsConverter(
                new Hashids($config['salt'], $config['min_length'])
            );

            $results[$name] = $this->runner->benchmark("config_$name", function () use ($hasher) {
                $hasher->encode(12345);
                $hasher->decode('test123');
            });
        }

        // Verify that configuration complexity doesn't severely impact performance
        $minimalTime = $results['minimal']->getMean();
        $complexTime = $results['complex']->getMean();

        $this->assertLessThan(
            $minimalTime * 5, // Complex should be less than 5x slower
            $complexTime,
            'Complex configuration too slow compared to minimal'
        );

        if (getenv('CI')) {
            echo "\nConfiguration Impact:\n";
            foreach ($results as $name => $result) {
                echo sprintf("  %s: %.4fms\n", $name, $result->getMean());
            }
        }
    }

    /**
     * Benchmark memory usage for large-scale operations.
     */
    public function testMemoryEfficiency(): void
    {
        $hasher = new HashidsConverter(new Hashids('test-salt', 10));

        $memoryProfile = $this->runner->profileMemory(function () use ($hasher) {
            for ($i = 0; $i < 1000; $i++) {
                $encoded = $hasher->encode($i);
                $hasher->decode($encoded);
            }
        }, 10); // Run 10 iterations of 1000 operations

        // Should use less than 10MB for 10,000 operations
        $this->assertLessThan(
            10 * 1024 * 1024,
            $memoryProfile['used'],
            sprintf('Memory usage too high: %s', $this->formatBytes($memoryProfile['used']))
        );

        if (getenv('CI')) {
            echo sprintf(
                "\nMemory Efficiency (10,000 operations):\n" .
                "  Memory used: %s\n" .
                "  Peak increase: %s\n",
                $this->formatBytes($memoryProfile['used']),
                $this->formatBytes($memoryProfile['peak_increase'])
            );
        }
    }

    /**
     * Benchmark concurrent encoding/decoding.
     */
    public function testConcurrentOperations(): void
    {
        $hasher = new HashidsConverter(new Hashids('test-salt', 10));

        $result = $this->runner->benchmark('concurrent_encode_decode', function () use ($hasher) {
            // Simulate concurrent operations
            $operations = [];
            for ($i = 0; $i < 10; $i++) {
                $operations[] = $hasher->encode($i * 100);
            }

            foreach ($operations as $encoded) {
                $hasher->decode($encoded);
            }
        });

        // Should handle 20 operations (10 encode + 10 decode) efficiently
        $this->assertLessThan(2, $result->getMean(),
            sprintf('Concurrent operations too slow: %.4fms', $result->getMean()));

        if (getenv('CI')) {
            echo sprintf(
                "\nConcurrent Operations (20 ops):\n" .
                "  Total: %.4fms\n" .
                "  Per operation: %.4fms\n",
                $result->getMean(),
                $result->getMean() / 20
            );
        }
    }

    /**
     * Benchmark factory instantiation overhead.
     */
    public function testFactoryOverhead(): void
    {
        $comparison = $this->runner->compare(
            'direct_hashids',
            function () {
                $hasher = new HashidsConverter(new Hashids('test-salt', 10));
                $hasher->encode(12345);
            },
            'via_factory',
            function () {
                $hasher = $this->factory->createConverter('default', [
                    'salt' => 'test-salt',
                    'min_length' => 10,
                ]);
                $hasher->encode(12345);
            }
        );

        // Factory overhead should be minimal (less than 50% slower)
        $this->assertLessThan(1.5, 1 / $comparison['speedup'],
            'Factory overhead too high');

        if (getenv('CI')) {
            echo sprintf(
                "\nFactory Overhead:\n" .
                "  Direct: %.4fms\n" .
                "  Via Factory: %.4fms\n" .
                "  Overhead: %.2fx\n",
                $comparison['a']->getMean(),
                $comparison['b']->getMean(),
                1 / $comparison['speedup']
            );
        }
    }

    /**
     * Format bytes to human readable.
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