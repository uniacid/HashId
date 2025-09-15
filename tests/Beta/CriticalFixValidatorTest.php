<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Beta;

use PHPUnit\Framework\TestCase;

class CriticalFixValidatorTest extends TestCase
{
    private CriticalFixValidator $validator;
    private string $fixLogDir;

    protected function setUp(): void
    {
        $this->fixLogDir = sys_get_temp_dir() . '/hashid-fixes';
        if (!is_dir($this->fixLogDir)) {
            mkdir($this->fixLogDir, 0777, true);
        }
        $this->validator = new CriticalFixValidator($this->fixLogDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->fixLogDir)) {
            $this->removeDirectory($this->fixLogDir);
        }
    }

    public function testTrackCriticalFix(): void
    {
        $fix = [
            'issue_id' => 'CRITICAL-001',
            'severity' => 'critical',
            'component' => 'RouterDecorator',
            'description' => 'Fix for route generation with multiple parameters',
            'files_changed' => [
                'src/Decorator/RouterDecorator.php',
                'tests/Decorator/RouterDecoratorTest.php',
            ],
            'test_coverage' => true,
            'backward_compatible' => true,
        ];

        $fixId = $this->validator->trackFix($fix);

        $this->assertNotEmpty($fixId);
        $this->assertTrue($this->validator->isFixTracked($fixId));
        $this->assertEquals($fix['issue_id'], $this->validator->getFixDetails($fixId)['issue_id']);
    }

    public function testValidateFix(): void
    {
        $fix = [
            'issue_id' => 'CRITICAL-002',
            'severity' => 'critical',
            'component' => 'ParameterProcessor',
            'files_changed' => ['src/ParametersProcessor/ParametersProcessor.php'],
            'test_coverage' => true,
            'backward_compatible' => true,
            'tests_added' => ['tests/ParametersProcessor/CriticalFixTest.php'],
            'tests_passing' => true,
        ];

        $fixId = $this->validator->trackFix($fix);
        $validation = $this->validator->validateFix($fixId);

        $this->assertTrue($validation['is_valid']);
        $this->assertTrue($validation['has_tests']);
        $this->assertTrue($validation['is_backward_compatible']);
        $this->assertTrue($validation['tests_pass']);
    }

    public function testValidateAllCriticalFixes(): void
    {
        // Track multiple fixes
        $fixes = [
            [
                'issue_id' => 'CRITICAL-003',
                'severity' => 'critical',
                'test_coverage' => true,
                'backward_compatible' => true,
                'tests_passing' => true,
            ],
            [
                'issue_id' => 'CRITICAL-004',
                'severity' => 'critical',
                'test_coverage' => true,
                'backward_compatible' => true,
                'tests_passing' => true,
            ],
            [
                'issue_id' => 'HIGH-001',
                'severity' => 'high',
                'test_coverage' => true,
                'backward_compatible' => true,
                'tests_passing' => true,
            ],
        ];

        foreach ($fixes as $fix) {
            $this->validator->trackFix($fix);
        }

        $validation = $this->validator->validateAllCriticalFixes();

        $this->assertEquals(2, $validation['critical_fixes_count']);
        $this->assertEquals(2, $validation['critical_fixes_validated']);
        $this->assertTrue($validation['all_critical_fixed']);
        $this->assertEmpty($validation['pending_critical']);
    }

    public function testGenerateFixReport(): void
    {
        $fixes = [
            [
                'issue_id' => 'CRITICAL-005',
                'severity' => 'critical',
                'component' => 'Annotation',
                'description' => 'Fix annotation parsing',
                'test_coverage' => true,
                'backward_compatible' => true,
                'tests_passing' => true,
                'fixed_at' => date('Y-m-d H:i:s'),
            ],
            [
                'issue_id' => 'HIGH-002',
                'severity' => 'high',
                'component' => 'Configuration',
                'description' => 'Fix configuration loading',
                'test_coverage' => true,
                'backward_compatible' => false,
                'tests_passing' => true,
                'fixed_at' => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($fixes as $fix) {
            $this->validator->trackFix($fix);
        }

        $report = $this->validator->generateFixReport();

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('critical_fixes', $report);
        $this->assertArrayHasKey('high_priority_fixes', $report);
        $this->assertArrayHasKey('backward_compatibility', $report);
        $this->assertArrayHasKey('test_coverage', $report);

        $this->assertEquals(1, $report['summary']['critical_count']);
        $this->assertEquals(1, $report['summary']['high_count']);
        $this->assertEquals(50, $report['backward_compatibility']['breaking_change_percentage']);
    }

    public function testRegressionValidation(): void
    {
        $regressionTests = [
            'encoding_decoding' => true,
            'route_generation' => true,
            'parameter_processing' => true,
            'doctrine_integration' => true,
            'twig_integration' => true,
            'performance_benchmarks' => true,
        ];

        $validation = $this->validator->validateNoRegressions($regressionTests);

        $this->assertTrue($validation['no_regressions']);
        $this->assertEmpty($validation['failed_tests']);
        $this->assertEquals(100, $validation['pass_rate']);
    }

    public function testPerformanceImpactValidation(): void
    {
        $performanceMetrics = [
            'encoding_speed_before' => 40000,
            'encoding_speed_after' => 42000,
            'decoding_speed_before' => 45000,
            'decoding_speed_after' => 44000,
            'memory_before_mb' => 32,
            'memory_after_mb' => 33,
        ];

        $validation = $this->validator->validatePerformanceImpact($performanceMetrics);

        $this->assertTrue($validation['acceptable_impact']);
        $this->assertLessThan(10, $validation['max_degradation_percentage']);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

class CriticalFixValidator
{
    private string $fixLogDir;
    private array $fixes = [];

    public function __construct(string $fixLogDir)
    {
        $this->fixLogDir = $fixLogDir;
        if (!is_dir($fixLogDir)) {
            mkdir($fixLogDir, 0777, true);
        }
        $this->loadFixes();
    }

    public function trackFix(array $fix): string
    {
        $fixId = uniqid('fix_', true);
        $fix['id'] = $fixId;
        $fix['tracked_at'] = date('Y-m-d H:i:s');

        $this->fixes[$fixId] = $fix;
        $this->saveFix($fixId, $fix);

        return $fixId;
    }

    public function isFixTracked(string $fixId): bool
    {
        return isset($this->fixes[$fixId]) || file_exists($this->fixLogDir . '/' . $fixId . '.json');
    }

    public function getFixDetails(string $fixId): ?array
    {
        if (isset($this->fixes[$fixId])) {
            return $this->fixes[$fixId];
        }

        $file = $this->fixLogDir . '/' . $fixId . '.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }

        return null;
    }

    public function validateFix(string $fixId): array
    {
        $fix = $this->getFixDetails($fixId);
        if (!$fix) {
            return ['is_valid' => false, 'error' => 'Fix not found'];
        }

        return [
            'is_valid' => true,
            'has_tests' => $fix['test_coverage'] ?? false,
            'is_backward_compatible' => $fix['backward_compatible'] ?? false,
            'tests_pass' => $fix['tests_passing'] ?? false,
            'severity' => $fix['severity'] ?? 'unknown',
        ];
    }

    public function validateAllCriticalFixes(): array
    {
        $criticalFixes = array_filter($this->fixes, fn($f) => ($f['severity'] ?? '') === 'critical');
        $validatedCount = 0;
        $pendingCritical = [];

        foreach ($criticalFixes as $fix) {
            $validation = $this->validateFix($fix['id']);
            if ($validation['is_valid'] && $validation['tests_pass']) {
                $validatedCount++;
            } else {
                $pendingCritical[] = $fix['issue_id'] ?? $fix['id'];
            }
        }

        return [
            'critical_fixes_count' => count($criticalFixes),
            'critical_fixes_validated' => $validatedCount,
            'all_critical_fixed' => $validatedCount === count($criticalFixes),
            'pending_critical' => $pendingCritical,
        ];
    }

    public function generateFixReport(): array
    {
        $criticalFixes = array_filter($this->fixes, fn($f) => ($f['severity'] ?? '') === 'critical');
        $highFixes = array_filter($this->fixes, fn($f) => ($f['severity'] ?? '') === 'high');

        $breakingChanges = array_filter($this->fixes, fn($f) => !($f['backward_compatible'] ?? true));
        $withTests = array_filter($this->fixes, fn($f) => $f['test_coverage'] ?? false);

        return [
            'summary' => [
                'total_fixes' => count($this->fixes),
                'critical_count' => count($criticalFixes),
                'high_count' => count($highFixes),
                'with_tests' => count($withTests),
                'breaking_changes' => count($breakingChanges),
            ],
            'critical_fixes' => array_map(fn($f) => [
                'issue_id' => $f['issue_id'] ?? '',
                'component' => $f['component'] ?? '',
                'description' => $f['description'] ?? '',
            ], $criticalFixes),
            'high_priority_fixes' => array_map(fn($f) => [
                'issue_id' => $f['issue_id'] ?? '',
                'component' => $f['component'] ?? '',
            ], $highFixes),
            'backward_compatibility' => [
                'breaking_changes' => count($breakingChanges),
                'breaking_change_percentage' => count($this->fixes) > 0 ?
                    (count($breakingChanges) / count($this->fixes)) * 100 : 0,
            ],
            'test_coverage' => [
                'fixes_with_tests' => count($withTests),
                'coverage_percentage' => count($this->fixes) > 0 ?
                    (count($withTests) / count($this->fixes)) * 100 : 0,
            ],
        ];
    }

    public function validateNoRegressions(array $testResults): array
    {
        $failedTests = array_filter($testResults, fn($passed) => !$passed);
        $passRate = count($testResults) > 0 ?
            (count($testResults) - count($failedTests)) / count($testResults) * 100 : 0;

        return [
            'no_regressions' => empty($failedTests),
            'failed_tests' => array_keys($failedTests),
            'pass_rate' => $passRate,
        ];
    }

    public function validatePerformanceImpact(array $metrics): array
    {
        $encodingDegradation = (($metrics['encoding_speed_before'] - $metrics['encoding_speed_after']) /
            $metrics['encoding_speed_before']) * 100;
        $decodingDegradation = (($metrics['decoding_speed_before'] - $metrics['decoding_speed_after']) /
            $metrics['decoding_speed_before']) * 100;
        $memoryIncrease = (($metrics['memory_after_mb'] - $metrics['memory_before_mb']) /
            $metrics['memory_before_mb']) * 100;

        $maxDegradation = max(abs($encodingDegradation), abs($decodingDegradation), abs($memoryIncrease));

        return [
            'acceptable_impact' => $maxDegradation < 10,
            'encoding_impact' => $encodingDegradation,
            'decoding_impact' => $decodingDegradation,
            'memory_impact' => $memoryIncrease,
            'max_degradation_percentage' => $maxDegradation,
        ];
    }

    private function loadFixes(): void
    {
        $files = glob($this->fixLogDir . '/fix_*.json');
        foreach ($files as $file) {
            $fix = json_decode(file_get_contents($file), true);
            if ($fix && isset($fix['id'])) {
                $this->fixes[$fix['id']] = $fix;
            }
        }
    }

    private function saveFix(string $fixId, array $fix): void
    {
        $file = $this->fixLogDir . '/' . $fixId . '.json';
        file_put_contents($file, json_encode($fix, JSON_PRETTY_PRINT));
    }
}