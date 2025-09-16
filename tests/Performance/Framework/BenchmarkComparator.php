<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Performance\Framework;

use RuntimeException;

/**
 * Compares benchmark results against baselines and targets.
 */
class BenchmarkComparator
{
    private array $baseline = [];
    private array $current = [];
    private array $comparisons = [];

    /**
     * Load baseline results from file.
     */
    public function loadBaseline(string $file): self
    {
        if (!file_exists($file)) {
            throw new RuntimeException("Baseline file not found: $file");
        }

        $data = json_decode(file_get_contents($file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid baseline JSON: " . json_last_error_msg());
        }

        $this->baseline = $data;
        return $this;
    }

    /**
     * Load current results from BenchmarkRunner.
     */
    public function loadResults(array $results): self
    {
        $this->current = [];
        foreach ($results as $name => $result) {
            if ($result instanceof BenchmarkResult) {
                $this->current[$name] = $result->toArray();
            } else {
                $this->current[$name] = $result;
            }
        }
        return $this;
    }

    /**
     * Compare current results against baseline.
     */
    public function compare(): array
    {
        $this->comparisons = [];

        foreach ($this->current as $name => $current) {
            if (!isset($this->baseline[$name])) {
                $this->comparisons[$name] = [
                    'status' => 'new',
                    'current' => $current,
                    'baseline' => null,
                    'improvement' => null,
                ];
                continue;
            }

            $baseline = $this->baseline[$name];
            $improvement = $this->calculateImprovement($baseline, $current);

            $this->comparisons[$name] = [
                'status' => $this->getStatus($improvement),
                'current' => $current,
                'baseline' => $baseline,
                'improvement' => $improvement,
            ];
        }

        // Check for removed benchmarks
        foreach ($this->baseline as $name => $baseline) {
            if (!isset($this->current[$name])) {
                $this->comparisons[$name] = [
                    'status' => 'removed',
                    'current' => null,
                    'baseline' => $baseline,
                    'improvement' => null,
                ];
            }
        }

        return $this->comparisons;
    }

    /**
     * Calculate improvement metrics.
     */
    private function calculateImprovement(array $baseline, array $current): array
    {
        $speedup = $baseline['mean'] / $current['mean'];
        $memoryDiff = $current['total_memory'] - $baseline['total_memory'];
        $percentChange = (($current['mean'] - $baseline['mean']) / $baseline['mean']) * 100;

        return [
            'speedup' => $speedup,
            'percent_change' => $percentChange,
            'memory_diff' => $memoryDiff,
            'mean_diff' => $current['mean'] - $baseline['mean'],
            'ops_diff' => $current['ops_per_second'] - $baseline['ops_per_second'],
        ];
    }

    /**
     * Determine status based on improvement.
     */
    private function getStatus(array $improvement): string
    {
        $speedup = $improvement['speedup'];

        if ($speedup >= 1.1) {
            return 'improved';
        } elseif ($speedup <= 0.9) {
            return 'regressed';
        } else {
            return 'unchanged';
        }
    }

    /**
     * Generate comparison report.
     */
    public function generateReport(bool $detailed = false): string
    {
        if (empty($this->comparisons)) {
            $this->compare();
        }

        $report = "=== Performance Comparison Report ===\n\n";

        $improved = 0;
        $regressed = 0;
        $unchanged = 0;
        $new = 0;
        $removed = 0;

        foreach ($this->comparisons as $name => $comparison) {
            switch ($comparison['status']) {
                case 'improved':
                    $improved++;
                    break;
                case 'regressed':
                    $regressed++;
                    break;
                case 'unchanged':
                    $unchanged++;
                    break;
                case 'new':
                    $new++;
                    break;
                case 'removed':
                    $removed++;
                    break;
            }

            if ($detailed) {
                $report .= $this->formatComparisonDetail($name, $comparison);
            }
        }

        $report .= "\n=== Summary ===\n";
        $report .= sprintf("Improved:  %d\n", $improved);
        $report .= sprintf("Regressed: %d\n", $regressed);
        $report .= sprintf("Unchanged: %d\n", $unchanged);
        $report .= sprintf("New:       %d\n", $new);
        $report .= sprintf("Removed:   %d\n", $removed);

        if ($regressed > 0) {
            $report .= "\n⚠️  Performance regressions detected!\n";
            foreach ($this->comparisons as $name => $comparison) {
                if ($comparison['status'] === 'regressed') {
                    $report .= sprintf(
                        "  - %s: %.2fx slower (%.2f%%)\n",
                        $name,
                        1 / $comparison['improvement']['speedup'],
                        abs($comparison['improvement']['percent_change'])
                    );
                }
            }
        }

        return $report;
    }

    /**
     * Format detailed comparison for a benchmark.
     */
    private function formatComparisonDetail(string $name, array $comparison): string
    {
        $detail = sprintf("\n%s:\n", $name);
        $detail .= sprintf("  Status: %s\n", $comparison['status']);

        if ($comparison['status'] === 'new') {
            $detail .= sprintf("  Mean: %.4fms\n", $comparison['current']['mean']);
            $detail .= sprintf("  Ops/sec: %.0f\n", $comparison['current']['ops_per_second']);
        } elseif ($comparison['status'] === 'removed') {
            $detail .= "  Benchmark removed from current results\n";
        } else {
            $improvement = $comparison['improvement'];
            $detail .= sprintf("  Baseline: %.4fms → Current: %.4fms\n",
                $comparison['baseline']['mean'],
                $comparison['current']['mean']
            );
            $detail .= sprintf("  Speedup: %.2fx\n", $improvement['speedup']);
            $detail .= sprintf("  Change: %+.2f%%\n", $improvement['percent_change']);
            $detail .= sprintf("  Ops/sec: %.0f → %.0f (%+.0f)\n",
                $comparison['baseline']['ops_per_second'],
                $comparison['current']['ops_per_second'],
                $improvement['ops_diff']
            );

            if ($improvement['memory_diff'] !== 0) {
                $detail .= sprintf("  Memory: %+s\n", $this->formatBytes($improvement['memory_diff']));
            }
        }

        return $detail;
    }

    /**
     * Check if all benchmarks meet performance targets.
     */
    public function meetsTargets(array $targets): bool
    {
        foreach ($targets as $name => $targetMs) {
            if (isset($this->current[$name]) && $this->current[$name]['mean'] > $targetMs) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get regressions.
     */
    public function getRegressions(): array
    {
        return array_filter($this->comparisons, fn($c) => $c['status'] === 'regressed');
    }

    /**
     * Get improvements.
     */
    public function getImprovements(): array
    {
        return array_filter($this->comparisons, fn($c) => $c['status'] === 'improved');
    }

    /**
     * Save current results as new baseline.
     */
    public function saveAsBaseline(string $file): void
    {
        file_put_contents($file, json_encode($this->current, JSON_PRETTY_PRINT));
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