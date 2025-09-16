<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Performance\Framework;

/**
 * Benchmark runner for performance testing.
 *
 * Provides utilities for running benchmarks with warmup,
 * statistical analysis, and memory profiling.
 */
class BenchmarkRunner
{
    private const DEFAULT_ITERATIONS = 1000;
    private const DEFAULT_WARMUP = 100;

    private array $results = [];
    private bool $collectMemory = true;
    private bool $verbose = false;

    public function __construct(
        private int $iterations = self::DEFAULT_ITERATIONS,
        private int $warmup = self::DEFAULT_WARMUP
    ) {
    }

    /**
     * Run a benchmark with warmup and statistical analysis.
     */
    public function benchmark(string $name, callable $callback, array $context = []): BenchmarkResult
    {
        // Warmup phase
        if ($this->verbose) {
            echo "Warming up $name...\n";
        }

        for ($i = 0; $i < $this->warmup; $i++) {
            $callback();
        }

        // Benchmark phase
        $times = [];
        $memories = [];

        gc_collect_cycles();
        $startMemory = memory_get_usage(true);

        for ($i = 0; $i < $this->iterations; $i++) {
            $memBefore = memory_get_usage(true);
            $start = hrtime(true);

            $callback();

            $elapsed = hrtime(true) - $start;
            $memAfter = memory_get_usage(true);

            $times[] = $elapsed / 1000000; // Convert to milliseconds

            if ($this->collectMemory) {
                $memories[] = $memAfter - $memBefore;
            }
        }

        $endMemory = memory_get_usage(true);
        $totalMemory = $endMemory - $startMemory;

        // Calculate statistics
        $result = new BenchmarkResult(
            name: $name,
            iterations: $this->iterations,
            times: $times,
            memories: $memories,
            totalMemory: $totalMemory,
            context: $context
        );

        $this->results[$name] = $result;

        if ($this->verbose) {
            $this->printResult($result);
        }

        return $result;
    }

    /**
     * Run comparative benchmarks between two implementations.
     */
    public function compare(
        string $nameA,
        callable $callbackA,
        string $nameB,
        callable $callbackB,
        array $context = []
    ): array {
        $resultA = $this->benchmark($nameA, $callbackA, $context);
        $resultB = $this->benchmark($nameB, $callbackB, $context);

        $comparison = [
            'a' => $resultA,
            'b' => $resultB,
            'speedup' => $resultA->getMean() / $resultB->getMean(),
            'memory_diff' => $resultB->getTotalMemory() - $resultA->getTotalMemory(),
        ];

        if ($this->verbose) {
            $this->printComparison($comparison);
        }

        return $comparison;
    }

    /**
     * Run a benchmark suite with multiple scenarios.
     */
    public function suite(string $name, array $scenarios): array
    {
        if ($this->verbose) {
            echo "\n=== Running Benchmark Suite: $name ===\n\n";
        }

        $suiteResults = [];

        foreach ($scenarios as $scenarioName => $callback) {
            $suiteResults[$scenarioName] = $this->benchmark(
                "$name::$scenarioName",
                $callback
            );
        }

        return $suiteResults;
    }

    /**
     * Profile memory usage for a callback.
     */
    public function profileMemory(callable $callback, int $iterations = 1000): array
    {
        $memoryProfile = [
            'before' => memory_get_usage(true),
            'peak_before' => memory_get_peak_usage(true),
        ];

        for ($i = 0; $i < $iterations; $i++) {
            $callback();
        }

        gc_collect_cycles();

        $memoryProfile['after'] = memory_get_usage(true);
        $memoryProfile['peak_after'] = memory_get_peak_usage(true);
        $memoryProfile['used'] = $memoryProfile['after'] - $memoryProfile['before'];
        $memoryProfile['peak_increase'] = $memoryProfile['peak_after'] - $memoryProfile['peak_before'];

        return $memoryProfile;
    }

    /**
     * Get all benchmark results.
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Enable/disable verbose output.
     */
    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;
        return $this;
    }

    /**
     * Enable/disable memory collection.
     */
    public function setCollectMemory(bool $collect): self
    {
        $this->collectMemory = $collect;
        return $this;
    }

    /**
     * Set number of iterations.
     */
    public function setIterations(int $iterations): self
    {
        $this->iterations = $iterations;
        return $this;
    }

    /**
     * Set warmup iterations.
     */
    public function setWarmup(int $warmup): self
    {
        $this->warmup = $warmup;
        return $this;
    }

    /**
     * Print a benchmark result.
     */
    private function printResult(BenchmarkResult $result): void
    {
        echo sprintf(
            "%s:\n" .
            "  Mean: %.4fms\n" .
            "  Median: %.4fms\n" .
            "  Min: %.4fms\n" .
            "  Max: %.4fms\n" .
            "  StdDev: %.4fms\n" .
            "  Memory: %s\n\n",
            $result->getName(),
            $result->getMean(),
            $result->getMedian(),
            $result->getMin(),
            $result->getMax(),
            $result->getStdDev(),
            $this->formatBytes($result->getTotalMemory())
        );
    }

    /**
     * Print a comparison result.
     */
    private function printComparison(array $comparison): void
    {
        $speedup = $comparison['speedup'];
        $memDiff = $comparison['memory_diff'];

        echo sprintf(
            "Comparison:\n" .
            "  %s vs %s\n" .
            "  Speedup: %.2fx %s\n" .
            "  Memory: %s\n\n",
            $comparison['a']->getName(),
            $comparison['b']->getName(),
            abs($speedup),
            $speedup > 1 ? 'faster' : 'slower',
            $memDiff > 0 ? '+' . $this->formatBytes($memDiff) : $this->formatBytes($memDiff)
        );
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

        return ($bytes < 0 ? '-' : '') . sprintf('%.2f %s', $value, $units[$unitIndex]);
    }
}