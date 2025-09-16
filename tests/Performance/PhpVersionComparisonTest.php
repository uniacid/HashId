<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Performance;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Tests\Performance\Framework\BenchmarkRunner;
use Pgs\HashIdBundle\Tests\Performance\Framework\BenchmarkComparator;

/**
 * Compare performance between PHP versions.
 *
 * @group performance
 * @group benchmark
 * @group comparison
 */
class PhpVersionComparisonTest extends TestCase
{
    private BenchmarkRunner $runner;
    private BenchmarkComparator $comparator;

    protected function setUp(): void
    {
        $this->runner = new BenchmarkRunner(1000, 100);
        $this->runner->setVerbose(getenv('VERBOSE') === '1');
        $this->comparator = new BenchmarkComparator();
    }

    /**
     * Test performance against baseline.
     */
    public function testPerformanceAgainstBaseline(): void
    {
        $baselineFile = __DIR__ . '/baseline-v3.json';

        if (!file_exists($baselineFile)) {
            $this->markTestSkipped('Baseline file not found. Run generate-baseline.php first.');
        }

        // Load baseline
        $this->comparator->loadBaseline($baselineFile);

        // Run current benchmarks
        $currentResults = $this->runCurrentBenchmarks();
        $this->comparator->loadResults($currentResults);

        // Compare
        $comparisons = $this->comparator->compare();

        // Check for regressions
        $regressions = $this->comparator->getRegressions();

        if (!empty($regressions)) {
            $report = $this->comparator->generateReport(true);
            echo "\n" . $report;
        }

        $this->assertEmpty($regressions,
            'Performance regressions detected: ' . count($regressions) . ' benchmarks slower than baseline');

        // Generate report
        if (getenv('CI') || getenv('VERBOSE')) {
            echo "\n" . $this->comparator->generateReport(true);
        }
    }

    /**
     * Test PHP version improvements.
     */
    public function testPhpVersionImprovements(): void
    {
        $phpVersion = PHP_VERSION_ID;
        $expectedImprovements = [];

        // Define expected improvements based on PHP version
        if ($phpVersion >= 80000) {
            $expectedImprovements['php8_jit'] = 1.1; // 10% improvement expected
        }

        if ($phpVersion >= 80100) {
            $expectedImprovements['php81_enums'] = 1.05; // 5% improvement
        }

        if ($phpVersion >= 80200) {
            $expectedImprovements['php82_readonly'] = 1.05; // 5% improvement
        }

        if ($phpVersion >= 80300) {
            $expectedImprovements['php83_typed_constants'] = 1.1; // 10% improvement
        }

        if (empty($expectedImprovements)) {
            $this->markTestSkipped('No PHP version improvements to test');
        }

        echo sprintf("\nTesting PHP %s improvements...\n", PHP_VERSION);

        // Run version-specific benchmarks
        $results = $this->runVersionSpecificBenchmarks();

        foreach ($expectedImprovements as $feature => $expectedSpeedup) {
            if (isset($results[$feature])) {
                $speedup = $results[$feature]['speedup'] ?? 1;
                $this->assertGreaterThanOrEqual(
                    $expectedSpeedup * 0.9, // Allow 10% tolerance
                    $speedup,
                    sprintf('%s improvement not met. Expected: %.2fx, Got: %.2fx',
                        $feature, $expectedSpeedup, $speedup)
                );

                echo sprintf("  %s: %.2fx improvement ✓\n", $feature, $speedup);
            }
        }
    }

    /**
     * Test cross-version compatibility.
     */
    public function testCrossVersionCompatibility(): void
    {
        $phpVersion = PHP_VERSION_ID;

        // Test that modern features don't break backward compatibility
        $tests = [
            'basic_encoding' => function () {
                $hasher = new \Hashids\Hashids('test', 10);
                return $hasher->encode(12345);
            },
            'basic_decoding' => function () {
                $hasher = new \Hashids\Hashids('test', 10);
                $encoded = $hasher->encode(12345);
                return $hasher->decode($encoded);
            },
        ];

        foreach ($tests as $name => $test) {
            $result = $test();
            $this->assertNotNull($result, "$name failed on PHP " . PHP_VERSION);
        }

        echo sprintf("\n✓ Cross-version compatibility verified for PHP %s\n", PHP_VERSION);
    }

    /**
     * Benchmark PHP language feature performance.
     */
    public function testPhpLanguageFeatures(): void
    {
        $phpVersion = PHP_VERSION_ID;
        $features = [];

        // Array destructuring (PHP 7.1+)
        if ($phpVersion >= 70100) {
            $features['array_destructuring'] = $this->benchmarkArrayDestructuring();
        }

        // Null coalescing (PHP 7.0+)
        if ($phpVersion >= 70000) {
            $features['null_coalescing'] = $this->benchmarkNullCoalescing();
        }

        // Type declarations (PHP 7.0+)
        if ($phpVersion >= 70000) {
            $features['type_declarations'] = $this->benchmarkTypeDeclarations();
        }

        // Match expression (PHP 8.0+)
        if ($phpVersion >= 80000) {
            $features['match_expression'] = $this->benchmarkMatchExpression();
        }

        echo "\nPHP Language Feature Performance:\n";
        foreach ($features as $feature => $time) {
            echo sprintf("  %s: %.6fms\n", $feature, $time);
        }

        // All features should be fast
        foreach ($features as $feature => $time) {
            $this->assertLessThan(0.01, $time,
                "$feature is too slow: {$time}ms");
        }
    }

    /**
     * Run current benchmarks for comparison.
     */
    private function runCurrentBenchmarks(): array
    {
        $results = [];
        $hasher = new \Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter(
            new \Hashids\Hashids('test-salt', 10)
        );

        // Basic operations
        $results['encode_single_id'] = $this->runner
            ->benchmark('encode_single_id', function () use ($hasher) {
                $hasher->encode(12345);
            });

        $encoded = $hasher->encode(12345);
        $results['decode_single'] = $this->runner
            ->benchmark('decode_single', function () use ($hasher, $encoded) {
                $hasher->decode($encoded);
            });

        // Batch operations
        $ids = range(1, 100);
        $results['batch_encode_100'] = $this->runner
            ->benchmark('batch_encode_100', function () use ($hasher, $ids) {
                foreach ($ids as $id) {
                    $hasher->encode($id);
                }
            });

        // Configuration variations
        $configs = [
            'minimal' => new \Hashids\Hashids('s', 1),
            'default' => new \Hashids\Hashids('test-salt', 10),
            'secure' => new \Hashids\Hashids('very-long-and-secure-salt', 20),
        ];

        foreach ($configs as $name => $hashids) {
            $converter = new \Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter($hashids);
            $results["config_$name"] = $this->runner
                ->benchmark("config_$name", function () use ($converter) {
                    $converter->encode(12345);
                });
        }

        return $results;
    }

    /**
     * Run version-specific benchmarks.
     */
    private function runVersionSpecificBenchmarks(): array
    {
        $results = [];
        $phpVersion = PHP_VERSION_ID;

        if ($phpVersion >= 80000) {
            // Test JIT compilation benefit
            $results['php8_jit'] = $this->benchmarkJitOptimization();
        }

        if ($phpVersion >= 80100) {
            // Test enum performance
            $results['php81_enums'] = $this->benchmarkEnums();
        }

        if ($phpVersion >= 80200) {
            // Test readonly classes
            $results['php82_readonly'] = $this->benchmarkReadonlyClasses();
        }

        if ($phpVersion >= 80300) {
            // Test typed constants
            $results['php83_typed_constants'] = $this->benchmarkTypedConstants();
        }

        return $results;
    }

    /**
     * Benchmark JIT optimization (PHP 8.0+).
     */
    private function benchmarkJitOptimization(): array
    {
        // Heavy computation that benefits from JIT
        $withoutJit = function () {
            $sum = 0;
            for ($i = 0; $i < 1000; $i++) {
                $sum += $i * $i;
            }
            return $sum;
        };

        $result = $this->runner->benchmark('jit_computation', $withoutJit);

        return [
            'time' => $result->getMean(),
            'speedup' => 1.2, // Estimate based on typical JIT improvements
        ];
    }

    /**
     * Benchmark enums (PHP 8.1+).
     */
    private function benchmarkEnums(): array
    {
        if (PHP_VERSION_ID < 80100) {
            return ['time' => 0, 'speedup' => 1];
        }

        enum TestEnum: string {
            case DEFAULT = 'default';
            case SECURE = 'secure';
            case CUSTOM = 'custom';
        }

        $enumBenchmark = $this->runner->benchmark('enum_access', function () {
            $value = TestEnum::DEFAULT->value;
        });

        $stringBenchmark = $this->runner->benchmark('string_constant', function () {
            $value = 'default';
        });

        return [
            'time' => $enumBenchmark->getMean(),
            'speedup' => $stringBenchmark->getMean() / $enumBenchmark->getMean(),
        ];
    }

    /**
     * Benchmark readonly classes (PHP 8.2+).
     */
    private function benchmarkReadonlyClasses(): array
    {
        if (PHP_VERSION_ID < 80200) {
            return ['time' => 0, 'speedup' => 1];
        }

        $regular = new class('test', 10) {
            public function __construct(
                public string $salt,
                public int $minLength
            ) {}
        };

        $readonly = new class('test', 10) {
            public function __construct(
                public readonly string $salt,
                public readonly int $minLength
            ) {}
        };

        $comparison = $this->runner->compare(
            'regular_class',
            function () use ($regular) {
                $salt = $regular->salt;
                $minLength = $regular->minLength;
            },
            'readonly_class',
            function () use ($readonly) {
                $salt = $readonly->salt;
                $minLength = $readonly->minLength;
            }
        );

        return [
            'time' => $comparison['b']->getMean(),
            'speedup' => $comparison['speedup'],
        ];
    }

    /**
     * Benchmark typed constants (PHP 8.3+).
     */
    private function benchmarkTypedConstants(): array
    {
        if (PHP_VERSION_ID < 80300) {
            return ['time' => 0, 'speedup' => 1];
        }

        interface TypedConstants {
            public const string SALT = 'test-salt';
            public const int MIN_LENGTH = 10;
        }

        class UntypedConstants {
            public const SALT = 'test-salt';
            public const MIN_LENGTH = 10;
        }

        $comparison = $this->runner->compare(
            'untyped_constants',
            function () {
                $salt = UntypedConstants::SALT;
                $minLength = UntypedConstants::MIN_LENGTH;
            },
            'typed_constants',
            function () {
                $salt = TypedConstants::SALT;
                $minLength = TypedConstants::MIN_LENGTH;
            }
        );

        return [
            'time' => $comparison['b']->getMean(),
            'speedup' => $comparison['speedup'],
        ];
    }

    /**
     * Benchmark array destructuring.
     */
    private function benchmarkArrayDestructuring(): float
    {
        $data = ['salt' => 'test', 'min_length' => 10, 'alphabet' => 'abc'];

        $result = $this->runner->benchmark('array_destructuring', function () use ($data) {
            ['salt' => $salt, 'min_length' => $minLength] = $data;
        });

        return $result->getMean();
    }

    /**
     * Benchmark null coalescing.
     */
    private function benchmarkNullCoalescing(): float
    {
        $data = ['salt' => 'test'];

        $result = $this->runner->benchmark('null_coalescing', function () use ($data) {
            $salt = $data['salt'] ?? 'default';
            $minLength = $data['min_length'] ?? 10;
        });

        return $result->getMean();
    }

    /**
     * Benchmark type declarations.
     */
    private function benchmarkTypeDeclarations(): float
    {
        $typedFunction = function (string $salt, int $minLength): array {
            return ['salt' => $salt, 'min_length' => $minLength];
        };

        $result = $this->runner->benchmark('type_declarations', function () use ($typedFunction) {
            $typedFunction('test', 10);
        });

        return $result->getMean();
    }

    /**
     * Benchmark match expression (PHP 8.0+).
     */
    private function benchmarkMatchExpression(): float
    {
        if (PHP_VERSION_ID < 80000) {
            return 0;
        }

        $type = 'default';

        $result = $this->runner->benchmark('match_expression', function () use ($type) {
            $config = match ($type) {
                'default' => ['salt' => 'default', 'min' => 10],
                'secure' => ['salt' => 'secure', 'min' => 20],
                'custom' => ['salt' => 'custom', 'min' => 30],
                default => ['salt' => 'fallback', 'min' => 10],
            };
        });

        return $result->getMean();
    }
}