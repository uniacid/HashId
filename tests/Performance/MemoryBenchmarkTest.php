<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Performance;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\RequestContext;
use Pgs\HashIdBundle\ParametersProcessor\ParametersProcessorInterface;
use Pgs\HashIdBundle\Service\DecodeControllerParameters;
use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Tests\Performance\Framework\BenchmarkRunner;
use Pgs\HashIdBundle\Decorator\RouterDecorator;
use Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter;
use Hashids\Hashids;

/**
 * Memory usage benchmarks for the HashId Bundle.
 *
 * @group performance
 * @group benchmark
 * @group memory
 */
class MemoryBenchmarkTest extends TestCase
{
    private BenchmarkRunner $runner;

    protected function setUp(): void
    {
        $this->runner = new BenchmarkRunner(100, 10); // Less iterations for memory tests
        $this->runner->setVerbose(getenv('VERBOSE') === '1');
    }

    /**
     * Test memory usage for basic encoding/decoding.
     */
    public function testBasicOperationMemory(): void
    {
        $hasher = new HashidsConverter(new Hashids('test-salt', 10));

        $memoryProfile = $this->runner->profileMemory(function () use ($hasher) {
            $encoded = $hasher->encode(12345);
            $decoded = $hasher->decode($encoded);
        }, 10000);

        // Should use less than 1MB for 10,000 basic operations
        $this->assertLessThan(
            1024 * 1024,
            $memoryProfile['used'],
            sprintf('Basic operations use too much memory: %s', $this->formatBytes($memoryProfile['used']))
        );

        if (getenv('CI')) {
            echo sprintf(
                "\nBasic Operation Memory (10,000 operations):\n" .
                "  Memory used: %s\n" .
                "  Peak increase: %s\n" .
                "  Per operation: %s\n",
                $this->formatBytes($memoryProfile['used']),
                $this->formatBytes($memoryProfile['peak_increase']),
                $this->formatBytes($memoryProfile['used'] / 10000)
            );
        }
    }

    /**
     * Test memory usage for batch operations.
     */
    public function testBatchOperationMemory(): void
    {
        $hasher = new HashidsConverter(new Hashids('test-salt', 10));

        // Test with different batch sizes
        $batchSizes = [100, 1000, 10000];
        $results = [];

        foreach ($batchSizes as $size) {
            $memoryProfile = $this->runner->profileMemory(function () use ($hasher, $size) {
                $ids = range(1, $size);
                $encoded = [];

                foreach ($ids as $id) {
                    $encoded[] = $hasher->encode($id);
                }

                foreach ($encoded as $value) {
                    $hasher->decode($value);
                }
            }, 1);

            $results[$size] = $memoryProfile;

            // Memory should scale linearly with batch size
            $expectedMemory = ($size / 100) * 512 * 1024; // ~512KB per 100 items
            $this->assertLessThan(
                $expectedMemory,
                $memoryProfile['used'],
                sprintf('Batch size %d uses too much memory: %s', $size, $this->formatBytes($memoryProfile['used']))
            );
        }

        if (getenv('CI')) {
            echo "\nBatch Operation Memory:\n";
            foreach ($results as $size => $profile) {
                echo sprintf(
                    "  Batch %d: %s (%.2f bytes/item)\n",
                    $size,
                    $this->formatBytes($profile['used']),
                    $profile['used'] / ($size * 2) // *2 for encode+decode
                );
            }
        }
    }

    /**
     * Test memory leaks in repeated operations.
     */
    public function testMemoryLeaks(): void
    {
        $hasher = new HashidsConverter(new Hashids('test-salt', 10));

        // Measure memory for first batch
        gc_collect_cycles();
        $startMemory = memory_get_usage(true);

        for ($i = 0; $i < 1000; $i++) {
            $encoded = $hasher->encode($i);
            $hasher->decode($encoded);
        }

        gc_collect_cycles();
        $firstBatchMemory = memory_get_usage(true) - $startMemory;

        // Measure memory for second batch
        gc_collect_cycles();
        $startMemory = memory_get_usage(true);

        for ($i = 1000; $i < 2000; $i++) {
            $encoded = $hasher->encode($i);
            $hasher->decode($encoded);
        }

        gc_collect_cycles();
        $secondBatchMemory = memory_get_usage(true) - $startMemory;

        // Second batch should use similar or less memory (no leak)
        $this->assertLessThanOrEqual(
            $firstBatchMemory * 1.1, // Allow 10% variance
            $secondBatchMemory,
            sprintf('Potential memory leak detected. First: %s, Second: %s',
                $this->formatBytes($firstBatchMemory),
                $this->formatBytes($secondBatchMemory))
        );

        if (getenv('CI')) {
            echo sprintf(
                "\nMemory Leak Test:\n" .
                "  First batch: %s\n" .
                "  Second batch: %s\n" .
                "  Difference: %s\n",
                $this->formatBytes($firstBatchMemory),
                $this->formatBytes($secondBatchMemory),
                $this->formatBytes($secondBatchMemory - $firstBatchMemory)
            );
        }
    }

    /**
     * Test router decoration memory overhead.
     */
    public function testRouterDecorationMemory(): void
    {
        $baseRouter = $this->createMockRouter();
        $processor = $this->createMockProcessor();
        $decodeService = $this->createMockDecodeService();

        $decoratedRouter = new RouterDecorator($baseRouter, $processor, $decodeService);

        $memoryProfile = $this->runner->profileMemory(function () use ($decoratedRouter) {
            for ($i = 0; $i < 1000; $i++) {
                $decoratedRouter->generate('test_route', [
                    'id' => $i,
                    'userId' => $i * 10,
                    'page' => $i % 10,
                ]);
            }
        }, 10);

        // Router decoration should use less than 5MB for 10,000 operations
        $this->assertLessThan(
            5 * 1024 * 1024,
            $memoryProfile['used'],
            sprintf('Router decoration uses too much memory: %s', $this->formatBytes($memoryProfile['used']))
        );

        if (getenv('CI')) {
            echo sprintf(
                "\nRouter Decoration Memory (10,000 operations):\n" .
                "  Memory used: %s\n" .
                "  Per route: %s\n",
                $this->formatBytes($memoryProfile['used']),
                $this->formatBytes($memoryProfile['used'] / 10000)
            );
        }
    }

    /**
     * Test memory usage with different configurations.
     */
    public function testConfigurationMemoryImpact(): void
    {
        $configs = [
            'minimal' => ['salt' => 's', 'min_length' => 1],
            'default' => ['salt' => 'test-salt-default', 'min_length' => 10],
            'complex' => ['salt' => str_repeat('x', 1000), 'min_length' => 50],
        ];

        $results = [];

        foreach ($configs as $name => $config) {
            $hasher = new HashidsConverter(
                new Hashids($config['salt'], $config['min_length'])
            );

            $memoryProfile = $this->runner->profileMemory(function () use ($hasher) {
                for ($i = 0; $i < 1000; $i++) {
                    $encoded = $hasher->encode($i);
                    $hasher->decode($encoded);
                }
            }, 10);

            $results[$name] = $memoryProfile;
        }

        // Complex configuration shouldn't use significantly more memory
        $minimalMemory = $results['minimal']['used'];
        $complexMemory = $results['complex']['used'];

        $this->assertLessThan(
            $minimalMemory * 2, // Complex should use less than 2x minimal
            $complexMemory,
            'Complex configuration uses too much memory compared to minimal'
        );

        if (getenv('CI')) {
            echo "\nConfiguration Memory Impact:\n";
            foreach ($results as $name => $profile) {
                echo sprintf("  %s: %s\n", $name, $this->formatBytes($profile['used']));
            }
        }
    }

    /**
     * Test memory usage for large ID values.
     */
    public function testLargeIdMemory(): void
    {
        $hasher = new HashidsConverter(new Hashids('test-salt', 10));

        $idSizes = [
            'small' => range(1, 100),
            'medium' => range(1000, 1100),
            'large' => range(1000000, 1000100),
            'huge' => array_map(fn($i) => PHP_INT_MAX - $i, range(0, 99)),
        ];

        $results = [];

        foreach ($idSizes as $size => $ids) {
            $memoryProfile = $this->runner->profileMemory(function () use ($hasher, $ids) {
                foreach ($ids as $id) {
                    $encoded = $hasher->encode($id);
                    $hasher->decode($encoded);
                }
            }, 10);

            $results[$size] = $memoryProfile;
        }

        // Memory usage should not increase significantly with ID size
        $smallMemory = $results['small']['used'];
        $hugeMemory = $results['huge']['used'];

        $this->assertLessThan(
            $smallMemory * 1.5, // Huge IDs should use less than 50% more memory
            $hugeMemory,
            'Large IDs use too much additional memory'
        );

        if (getenv('CI')) {
            echo "\nLarge ID Memory Impact:\n";
            foreach ($results as $size => $profile) {
                echo sprintf("  %s IDs: %s\n", $size, $this->formatBytes($profile['used']));
            }
        }
    }

    /**
     * Test memory usage for concurrent-like operations.
     */
    public function testConcurrentMemory(): void
    {
        $hasher = new HashidsConverter(new Hashids('test-salt', 10));

        // Simulate multiple "concurrent" encoding streams
        $streams = 10;
        $opsPerStream = 100;

        $memoryProfile = $this->runner->profileMemory(function () use ($hasher, $streams, $opsPerStream) {
            $encodedStreams = [];

            // Encode phase (all streams)
            for ($stream = 0; $stream < $streams; $stream++) {
                $encodedStreams[$stream] = [];
                for ($op = 0; $op < $opsPerStream; $op++) {
                    $encodedStreams[$stream][] = $hasher->encode($stream * 1000 + $op);
                }
            }

            // Decode phase (all streams)
            foreach ($encodedStreams as $stream => $encoded) {
                foreach ($encoded as $value) {
                    $hasher->decode($value);
                }
            }
        }, 1);

        $totalOps = $streams * $opsPerStream * 2; // encode + decode
        $perOpMemory = $memoryProfile['used'] / $totalOps;

        // Should use less than 1KB per operation
        $this->assertLessThan(
            1024,
            $perOpMemory,
            sprintf('Concurrent operations use too much memory per operation: %s',
                $this->formatBytes($perOpMemory))
        );

        if (getenv('CI')) {
            echo sprintf(
                "\nConcurrent Memory Usage:\n" .
                "  Streams: %d\n" .
                "  Operations: %d\n" .
                "  Total memory: %s\n" .
                "  Per operation: %s\n",
                $streams,
                $totalOps,
                $this->formatBytes($memoryProfile['used']),
                $this->formatBytes($perOpMemory)
            );
        }
    }

    /**
     * Test peak memory usage.
     */
    public function testPeakMemoryUsage(): void
    {
        $hasher = new HashidsConverter(new Hashids('test-salt', 10));

        // Track peak memory during large batch
        $peakBefore = memory_get_peak_usage(true);

        // Large batch operation
        $ids = range(1, 50000);
        $encoded = [];

        foreach ($ids as $id) {
            $encoded[] = $hasher->encode($id);
        }

        $peakAfter = memory_get_peak_usage(true);
        $peakIncrease = $peakAfter - $peakBefore;

        // Peak should not exceed 50MB for 50,000 operations
        $this->assertLessThan(
            50 * 1024 * 1024,
            $peakIncrease,
            sprintf('Peak memory too high: %s', $this->formatBytes($peakIncrease))
        );

        // Clean up and check memory is released
        unset($encoded);
        gc_collect_cycles();

        $memoryAfterCleanup = memory_get_usage(true);

        if (getenv('CI')) {
            echo sprintf(
                "\nPeak Memory Usage (50,000 operations):\n" .
                "  Peak increase: %s\n" .
                "  After cleanup: %s\n" .
                "  Per operation: %s\n",
                $this->formatBytes($peakIncrease),
                $this->formatBytes($memoryAfterCleanup - $peakBefore),
                $this->formatBytes($peakIncrease / 50000)
            );
        }
    }

    /**
     * Create a mock router.
     */
    private function createMockRouter(): object
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('/test/123');
        $router->method('getContext')->willReturn(new RequestContext());
        return $router;
    }

    /**
     * Create a mock processor.
     */
    private function createMockProcessor(): object
    {
        $processor = $this->createMock(ParametersProcessorInterface::class);
        $processor->method('process')->willReturnArgument(0);
        $processor->method('needToProcess')->willReturn(false);
        $processor->method('setParametersToProcess')->willReturnSelf();
        return $processor;
    }

    /**
     * Create a mock decode service.
     */
    private function createMockDecodeService(): object
    {
        return $this->createMock(DecodeControllerParameters::class);
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