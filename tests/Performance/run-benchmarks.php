#!/usr/bin/env php
<?php

/**
 * Run complete performance benchmark suite and generate reports.
 *
 * Usage: php tests/Performance/run-benchmarks.php [--verbose] [--format=markdown|json|csv]
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Pgs\HashIdBundle\Tests\Performance\Framework\BenchmarkRunner;
use Pgs\HashIdBundle\Tests\Performance\Framework\BenchmarkComparator;
use Pgs\HashIdBundle\Tests\Performance\ReportGenerator;
use Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter;
use Hashids\Hashids;

// Parse command line arguments
$options = getopt('', ['verbose', 'format::', 'save-baseline']);
$verbose = isset($options['verbose']);
$format = $options['format'] ?? 'markdown';
$saveBaseline = isset($options['save-baseline']);

echo "╔════════════════════════════════════════════════════════╗\n";
echo "║     HashId Bundle v4.0 Performance Benchmark Suite     ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

$runner = new BenchmarkRunner(1000, 100);
$runner->setVerbose($verbose);

$reportGenerator = new ReportGenerator();
$reportGenerator->addMetadata([
    'php_version' => PHP_VERSION,
    'date' => date('Y-m-d H:i:s'),
    'iterations' => 1000,
    'warmup' => 100,
]);

$allResults = [];

// ===== 1. ENCODING/DECODING BENCHMARKS =====
echo "📊 Running Encoding/Decoding Benchmarks...\n";

$hasher = new HashidsConverter(new Hashids('test-salt', 10));

$encodingResults = [];

// Single encoding
$encodingResults['encode_single_id'] = $runner->benchmark('encode_single_id', function () use ($hasher) {
    $hasher->encode(12345);
});

// Single decoding
$encoded = $hasher->encode(12345);
$encodingResults['decode_single'] = $runner->benchmark('decode_single', function () use ($hasher, $encoded) {
    $hasher->decode($encoded);
});

// Batch operations
$ids = range(1, 100);
$encodingResults['batch_encode_100'] = $runner->benchmark('batch_encode_100', function () use ($hasher, $ids) {
    foreach ($ids as $id) {
        $hasher->encode($id);
    }
});

$reportGenerator->addResults('encoding', $encodingResults);
$allResults = array_merge($allResults, $encodingResults);

echo "✅ Encoding/Decoding benchmarks complete\n\n";

// ===== 2. MEMORY BENCHMARKS =====
echo "💾 Running Memory Benchmarks...\n";

$memoryResults = [];

$memoryProfile = $runner->profileMemory(function () use ($hasher) {
    for ($i = 0; $i < 1000; $i++) {
        $encoded = $hasher->encode($i);
        $hasher->decode($encoded);
    }
}, 10);

$memoryResults['memory_1000_ops'] = [
    'memory_used' => $memoryProfile['used'],
    'peak_increase' => $memoryProfile['peak_increase'],
];

$reportGenerator->addResults('memory', $memoryResults);

echo "✅ Memory benchmarks complete\n\n";

// ===== 3. CONFIGURATION BENCHMARKS =====
echo "⚙️  Running Configuration Benchmarks...\n";

$configResults = [];

$configs = [
    'minimal' => ['salt' => 's', 'min_length' => 1],
    'default' => ['salt' => 'test-salt', 'min_length' => 10],
    'secure' => ['salt' => 'very-long-and-secure-salt', 'min_length' => 20],
];

foreach ($configs as $name => $config) {
    $configHasher = new HashidsConverter(
        new Hashids($config['salt'], $config['min_length'])
    );

    $configResults["config_$name"] = $runner->benchmark("config_$name", function () use ($configHasher) {
        $configHasher->encode(12345);
    });
}

$reportGenerator->addResults('configuration', $configResults);
$allResults = array_merge($allResults, $configResults);

echo "✅ Configuration benchmarks complete\n\n";

// ===== 4. COMPARISON WITH BASELINE =====
echo "📈 Comparing with baseline...\n";

$baselineFile = __DIR__ . '/baseline-v3.json';
if (file_exists($baselineFile)) {
    $comparator = new BenchmarkComparator();
    $comparator->loadBaseline($baselineFile);
    $comparator->loadResults($allResults);

    $comparisons = $comparator->compare();
    $regressions = $comparator->getRegressions();
    $improvements = $comparator->getImprovements();

    echo sprintf("  Improvements: %d\n", count($improvements));
    echo sprintf("  Regressions: %d\n", count($regressions));
    echo sprintf("  Unchanged: %d\n", count($comparisons) - count($improvements) - count($regressions));

    if (!empty($regressions)) {
        echo "\n⚠️  Performance regressions detected:\n";
        foreach ($regressions as $name => $regression) {
            echo sprintf("  - %s: %.2fx slower\n",
                $name,
                1 / $regression['improvement']['speedup']
            );
        }
    }
} else {
    echo "  No baseline found. Run generate-baseline.php first.\n";
}

echo "\n";

// ===== 5. GENERATE REPORTS =====
echo "📝 Generating reports...\n";

// Generate report in requested format
$reportContent = match ($format) {
    'json' => $reportGenerator->generateJson(),
    'csv' => $reportGenerator->generateCsv(),
    default => $reportGenerator->generateMarkdown(),
};

// Save report
$reportFile = __DIR__ . '/reports/performance-report-' . date('Y-m-d-His') . '.' . match ($format) {
    'json' => 'json',
    'csv' => 'csv',
    default => 'md',
};

file_put_contents($reportFile, $reportContent);
echo "  Report saved to: $reportFile\n";

// Save baseline if requested
if ($saveBaseline) {
    $baselineData = [];
    foreach ($allResults as $name => $result) {
        if ($result instanceof \Pgs\HashIdBundle\Tests\Performance\Framework\BenchmarkResult) {
            $baselineData[$name] = $result->toArray();
        }
    }

    $newBaselineFile = __DIR__ . '/baseline-v4-' . date('Y-m-d') . '.json';
    file_put_contents($newBaselineFile, json_encode($baselineData, JSON_PRETTY_PRINT));
    echo "  New baseline saved to: $newBaselineFile\n";
}

// ===== 6. PERFORMANCE SUMMARY =====
echo "\n";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║                  Performance Summary                   ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Display key metrics
if (isset($encodingResults['encode_single_id'])) {
    echo sprintf("🔹 Single Encoding: %.4fms (%.0f ops/sec)\n",
        $encodingResults['encode_single_id']->getMean(),
        $encodingResults['encode_single_id']->getOpsPerSecond()
    );
}

if (isset($encodingResults['decode_single'])) {
    echo sprintf("🔹 Single Decoding: %.4fms (%.0f ops/sec)\n",
        $encodingResults['decode_single']->getMean(),
        $encodingResults['decode_single']->getOpsPerSecond()
    );
}

if (isset($encodingResults['batch_encode_100'])) {
    echo sprintf("🔹 Batch (100): %.4fms total, %.4fms per item\n",
        $encodingResults['batch_encode_100']->getMean(),
        $encodingResults['batch_encode_100']->getMean() / 100
    );
}

if (isset($memoryResults['memory_1000_ops'])) {
    echo sprintf("🔹 Memory (10,000 ops): %s\n",
        formatBytes($memoryResults['memory_1000_ops']['memory_used'])
    );
}

// Check targets
echo "\n📋 Performance Targets:\n";
$targets = [
    'Encoding < 0.1ms' => isset($encodingResults['encode_single_id']) &&
                          $encodingResults['encode_single_id']->getMean() < 0.1,
    'Decoding < 0.1ms' => isset($encodingResults['decode_single']) &&
                          $encodingResults['decode_single']->getMean() < 0.1,
    'Batch 100 < 10ms' => isset($encodingResults['batch_encode_100']) &&
                          $encodingResults['batch_encode_100']->getMean() < 10,
    'Memory < 10MB/10k' => isset($memoryResults['memory_1000_ops']) &&
                          $memoryResults['memory_1000_ops']['memory_used'] < 10 * 1024 * 1024,
];

$allTargetsMet = true;
foreach ($targets as $target => $met) {
    echo sprintf("  %s %s\n", $met ? '✅' : '❌', $target);
    if (!$met) {
        $allTargetsMet = false;
    }
}

echo "\n";
if ($allTargetsMet) {
    echo "🎉 All performance targets met!\n";
} else {
    echo "⚠️  Some performance targets not met. Review the report for details.\n";
}

echo "\n✨ Benchmark suite complete!\n";

/**
 * Format bytes to human readable.
 */
function formatBytes(int $bytes): string
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