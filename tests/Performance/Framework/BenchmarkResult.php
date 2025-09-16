<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Performance\Framework;

/**
 * Stores and analyzes benchmark results.
 */
class BenchmarkResult
{
    private float $mean;
    private float $median;
    private float $min;
    private float $max;
    private float $stdDev;
    private float $percentile95;
    private float $percentile99;

    public function __construct(
        private readonly string $name,
        private readonly int $iterations,
        private array $times,
        private readonly array $memories,
        private readonly int $totalMemory,
        private readonly array $context = []
    ) {
        $this->calculateStatistics();
    }

    /**
     * Calculate statistical metrics from raw times.
     */
    private function calculateStatistics(): void
    {
        sort($this->times);

        $this->min = min($this->times);
        $this->max = max($this->times);
        $this->mean = array_sum($this->times) / count($this->times);

        // Calculate median
        $count = count($this->times);
        $middle = floor($count / 2);
        $this->median = $count % 2 === 0
            ? ($this->times[$middle - 1] + $this->times[$middle]) / 2
            : $this->times[$middle];

        // Calculate standard deviation
        $variance = 0;
        foreach ($this->times as $time) {
            $variance += pow($time - $this->mean, 2);
        }
        $this->stdDev = sqrt($variance / count($this->times));

        // Calculate percentiles
        $this->percentile95 = $this->getPercentile(95);
        $this->percentile99 = $this->getPercentile(99);
    }

    /**
     * Get a specific percentile value.
     */
    private function getPercentile(int $percentile): float
    {
        $index = (int) ceil(($percentile / 100) * count($this->times)) - 1;
        return $this->times[$index];
    }

    /**
     * Get the benchmark name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get mean execution time in milliseconds.
     */
    public function getMean(): float
    {
        return $this->mean;
    }

    /**
     * Get median execution time in milliseconds.
     */
    public function getMedian(): float
    {
        return $this->median;
    }

    /**
     * Get minimum execution time in milliseconds.
     */
    public function getMin(): float
    {
        return $this->min;
    }

    /**
     * Get maximum execution time in milliseconds.
     */
    public function getMax(): float
    {
        return $this->max;
    }

    /**
     * Get standard deviation in milliseconds.
     */
    public function getStdDev(): float
    {
        return $this->stdDev;
    }

    /**
     * Get 95th percentile in milliseconds.
     */
    public function getPercentile95(): float
    {
        return $this->percentile95;
    }

    /**
     * Get 99th percentile in milliseconds.
     */
    public function getPercentile99(): float
    {
        return $this->percentile99;
    }

    /**
     * Get number of iterations.
     */
    public function getIterations(): int
    {
        return $this->iterations;
    }

    /**
     * Get total memory usage in bytes.
     */
    public function getTotalMemory(): int
    {
        return $this->totalMemory;
    }

    /**
     * Get average memory per iteration in bytes.
     */
    public function getAverageMemory(): float
    {
        return empty($this->memories) ? 0 : array_sum($this->memories) / count($this->memories);
    }

    /**
     * Get peak memory usage in bytes.
     */
    public function getPeakMemory(): int
    {
        return empty($this->memories) ? 0 : max($this->memories);
    }

    /**
     * Get raw timing data.
     */
    public function getTimes(): array
    {
        return $this->times;
    }

    /**
     * Get raw memory data.
     */
    public function getMemories(): array
    {
        return $this->memories;
    }

    /**
     * Get context data.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get operations per second.
     */
    public function getOpsPerSecond(): float
    {
        return 1000 / $this->mean; // Since mean is in milliseconds
    }

    /**
     * Convert to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'iterations' => $this->iterations,
            'mean' => $this->mean,
            'median' => $this->median,
            'min' => $this->min,
            'max' => $this->max,
            'std_dev' => $this->stdDev,
            'percentile_95' => $this->percentile95,
            'percentile_99' => $this->percentile99,
            'ops_per_second' => $this->getOpsPerSecond(),
            'total_memory' => $this->totalMemory,
            'average_memory' => $this->getAverageMemory(),
            'peak_memory' => $this->getPeakMemory(),
            'context' => $this->context,
        ];
    }

    /**
     * Check if this result meets a performance target.
     */
    public function meetsTarget(float $targetMs): bool
    {
        return $this->mean <= $targetMs;
    }

    /**
     * Compare with another result.
     */
    public function compareTo(BenchmarkResult $other): array
    {
        return [
            'speedup' => $other->getMean() / $this->mean,
            'memory_diff' => $this->totalMemory - $other->totalMemory,
            'mean_diff' => $this->mean - $other->getMean(),
            'median_diff' => $this->median - $other->getMedian(),
        ];
    }
}