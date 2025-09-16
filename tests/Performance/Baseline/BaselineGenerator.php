<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Performance\Baseline;

use Pgs\HashIdBundle\Tests\Performance\Framework\BenchmarkRunner;
use Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter;
use Hashids\Hashids;

/**
 * Generates baseline performance metrics for v3.x.
 *
 * This can be run on PHP 7.2 with Symfony 4.4 to capture
 * baseline metrics for comparison with v4.0.
 */
class BaselineGenerator
{
    private readonly BenchmarkRunner $runner;
    private array $results = [];

    public function __construct()
    {
        $this->runner = new BenchmarkRunner(1000, 100);
    }

    /**
     * Generate complete baseline metrics.
     */
    public function generate(): array
    {
        echo "Generating baseline performance metrics...\n\n";

        $this->benchmarkEncoding();
        $this->benchmarkDecoding();
        $this->benchmarkBatchOperations();
        $this->benchmarkMemoryUsage();
        $this->benchmarkConfigVariations();
        $this->benchmarkScalability();

        return $this->results;
    }

    /**
     * Benchmark basic encoding operations.
     */
    private function benchmarkEncoding(): void
    {
        echo "Benchmarking encoding operations...\n";

        $hasher = new HashidsConverter(new Hashids('baseline-salt', 10));

        // Single ID encoding
        $this->results['encode_single_id'] = $this->runner
            ->benchmark('encode_single_id', function () use ($hasher) {
                $hasher->encode(12345);
            })
            ->toArray();

        // Various ID sizes
        $idSizes = [
            'small' => 1,
            'medium' => 123456,
            'large' => 999999999,
        ];

        foreach ($idSizes as $size => $id) {
            $this->results["encode_{$size}_id"] = $this->runner
                ->benchmark("encode_{$size}_id", function () use ($hasher, $id) {
                    $hasher->encode($id);
                })
                ->toArray();
        }
    }

    /**
     * Benchmark basic decoding operations.
     */
    private function benchmarkDecoding(): void
    {
        echo "Benchmarking decoding operations...\n";

        $hasher = new HashidsConverter(new Hashids('baseline-salt', 10));

        // Pre-encode values for decoding
        $encoded = [
            'single' => $hasher->encode(12345),
            'small' => $hasher->encode(1),
            'medium' => $hasher->encode(123456),
            'large' => $hasher->encode(999999999),
        ];

        foreach ($encoded as $type => $value) {
            $this->results["decode_{$type}"] = $this->runner
                ->benchmark("decode_{$type}", function () use ($hasher, $value) {
                    $hasher->decode($value);
                })
                ->toArray();
        }
    }

    /**
     * Benchmark batch operations.
     */
    private function benchmarkBatchOperations(): void
    {
        echo "Benchmarking batch operations...\n";

        $hasher = new HashidsConverter(new Hashids('baseline-salt', 10));

        // Batch encode
        $ids = range(1, 100);
        $this->results['batch_encode_100'] = $this->runner
            ->benchmark('batch_encode_100', function () use ($hasher, $ids) {
                foreach ($ids as $id) {
                    $hasher->encode($id);
                }
            })
            ->toArray();

        // Batch decode
        $encoded = array_map($hasher->encode(...), $ids);
        $this->results['batch_decode_100'] = $this->runner
            ->benchmark('batch_decode_100', function () use ($hasher, $encoded) {
                foreach ($encoded as $value) {
                    $hasher->decode($value);
                }
            })
            ->toArray();

        // Mixed operations
        $this->results['mixed_operations'] = $this->runner
            ->benchmark('mixed_operations', function () use ($hasher) {
                for ($i = 0; $i < 50; $i++) {
                    $encoded = $hasher->encode($i);
                    $hasher->decode($encoded);
                }
            })
            ->toArray();
    }

    /**
     * Benchmark memory usage.
     */
    private function benchmarkMemoryUsage(): void
    {
        echo "Benchmarking memory usage...\n";

        $hasher = new HashidsConverter(new Hashids('baseline-salt', 10));

        // Memory for 1000 operations
        $memoryProfile = $this->runner->profileMemory(function () use ($hasher) {
            for ($i = 0; $i < 1000; $i++) {
                $encoded = $hasher->encode($i);
                $hasher->decode($encoded);
            }
        }, 10);

        $this->results['memory_1000_ops'] = [
            'operations' => 10000,
            'memory_used' => $memoryProfile['used'],
            'peak_increase' => $memoryProfile['peak_increase'],
            'per_operation' => $memoryProfile['used'] / 10000,
        ];

        // Memory for large batch
        $memoryProfile = $this->runner->profileMemory(function () use ($hasher) {
            $ids = range(1, 10000);
            $encoded = [];
            foreach ($ids as $id) {
                $encoded[] = $hasher->encode($id);
            }
            foreach ($encoded as $value) {
                $hasher->decode($value);
            }
        }, 1);

        $this->results['memory_large_batch'] = [
            'operations' => 20000,
            'memory_used' => $memoryProfile['used'],
            'peak_increase' => $memoryProfile['peak_increase'],
            'per_operation' => $memoryProfile['used'] / 20000,
        ];
    }

    /**
     * Benchmark different configurations.
     */
    private function benchmarkConfigVariations(): void
    {
        echo "Benchmarking configuration variations...\n";

        $configs = [
            'minimal' => ['salt' => 's', 'min_length' => 1],
            'default' => ['salt' => 'baseline-salt', 'min_length' => 10],
            'secure' => ['salt' => 'very-long-and-secure-salt-string', 'min_length' => 20],
            'complex' => ['salt' => str_repeat('x', 100), 'min_length' => 30],
        ];

        foreach ($configs as $name => $config) {
            $hasher = new HashidsConverter(
                new Hashids($config['salt'], $config['min_length'])
            );

            $this->results["config_{$name}"] = $this->runner
                ->benchmark("config_{$name}", function () use ($hasher) {
                    $hasher->encode(12345);
                })
                ->toArray();
        }
    }

    /**
     * Benchmark scalability.
     */
    private function benchmarkScalability(): void
    {
        echo "Benchmarking scalability...\n";

        $hasher = new HashidsConverter(new Hashids('baseline-salt', 10));

        // Test with increasing load
        $loads = [10, 100, 1000];

        foreach ($loads as $load) {
            $this->results["scale_{$load}_ops"] = $this->runner
                ->benchmark("scale_{$load}_ops", function () use ($hasher, $load) {
                    for ($i = 0; $i < $load; $i++) {
                        $hasher->encode($i);
                    }
                })
                ->toArray();
        }

        // Concurrent-like operations
        $this->results['concurrent_simulation'] = $this->runner
            ->benchmark('concurrent_simulation', function () use ($hasher) {
                $operations = [];
                for ($i = 0; $i < 10; $i++) {
                    $operations[] = $hasher->encode($i * 100);
                }
                foreach ($operations as $encoded) {
                    $hasher->decode($encoded);
                }
            })
            ->toArray();
    }

    /**
     * Save baseline to file.
     */
    public function save(string $file): void
    {
        $metadata = [
            '_metadata' => [
                'php_version' => PHP_VERSION,
                'generated_at' => date('Y-m-d H:i:s'),
                'iterations' => 1000,
                'warmup' => 100,
            ],
        ];

        $data = array_merge($metadata, $this->results);

        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        echo "\nBaseline saved to: $file\n";
    }

    /**
     * Print summary of baseline metrics.
     */
    public function printSummary(): void
    {
        echo "\n=== Baseline Summary ===\n\n";

        if (isset($this->results['encode_single_id'])) {
            echo sprintf("Single encoding: %.4fms (%.0f ops/sec)\n",
                $this->results['encode_single_id']['mean'],
                $this->results['encode_single_id']['ops_per_second']
            );
        }

        if (isset($this->results['decode_single'])) {
            echo sprintf("Single decoding: %.4fms (%.0f ops/sec)\n",
                $this->results['decode_single']['mean'],
                $this->results['decode_single']['ops_per_second']
            );
        }

        if (isset($this->results['batch_encode_100'])) {
            echo sprintf("Batch encode (100): %.4fms total, %.4fms per item\n",
                $this->results['batch_encode_100']['mean'],
                $this->results['batch_encode_100']['mean'] / 100
            );
        }

        if (isset($this->results['memory_1000_ops'])) {
            echo sprintf("Memory (1000 ops): %s\n",
                $this->formatBytes($this->results['memory_1000_ops']['memory_used'])
            );
        }

        echo "\nPHP Version: " . PHP_VERSION . "\n";
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