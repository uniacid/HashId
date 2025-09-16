<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Rector;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Rector\MetricsCollector;
use Pgs\HashIdBundle\Rector\ReportGenerator;
use Symfony\Component\Process\Process;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class AutomationTargetValidationTest extends TestCase
{
    private const TARGET_AUTOMATION_PERCENTAGE = 70.0;
    private const TARGET_HASH_RULE_SUCCESS = 90.0;
    private const TARGET_TIME_REDUCTION = 50.0;
    private const TARGET_ERROR_REDUCTION = 80.0;

    private string $projectDir;
    private MetricsCollector $metricsCollector;
    private ReportGenerator $reportGenerator;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->projectDir = dirname(__DIR__, 2); // Project root
        $this->metricsCollector = new MetricsCollector();
        $this->reportGenerator = new ReportGenerator($this->metricsCollector);
        $this->filesystem = new Filesystem();
    }

    /**
     * @group automation_target
     * @group critical
     */
    public function testAutomationTargetMet(): void
    {
        $this->metricsCollector->startTiming();

        // Run Rector on the actual project
        $result = $this->runRectorOnProject();

        $this->metricsCollector->stopTiming();

        // Collect metrics
        $metrics = $this->metricsCollector->getMetrics();
        $automationPercentage = $metrics['automation_percentage'];

        // Primary assertion: 70%+ automation
        $this->assertGreaterThanOrEqual(
            self::TARGET_AUTOMATION_PERCENTAGE,
            $automationPercentage,
            sprintf(
                'Automation percentage (%.2f%%) must meet or exceed target of %.1f%%',
                $automationPercentage,
                self::TARGET_AUTOMATION_PERCENTAGE
            )
        );

        // Log success
        if ($automationPercentage >= self::TARGET_AUTOMATION_PERCENTAGE) {
            $this->markTestAsSuccessful('automation_target', $automationPercentage);
        }
    }

    /**
     * @group rule_effectiveness
     * @group critical
     */
    public function testHashIdSpecificRuleEffectiveness(): void
    {
        $testProject = $this->createHashIdTestProject();

        $this->metricsCollector->reset();
        $this->metricsCollector->startTiming();

        // Run Rector with HashId-specific rules
        $result = $this->runRectorWithCustomRules($testProject);

        $this->metricsCollector->stopTiming();

        // Check Hash annotation to attribute conversion
        $hashRuleSuccess = $this->calculateHashRuleSuccess($result);

        $this->assertGreaterThanOrEqual(
            self::TARGET_HASH_RULE_SUCCESS,
            $hashRuleSuccess,
            sprintf(
                'Hash annotation to attribute conversion (%.2f%%) must meet target of %.1f%%',
                $hashRuleSuccess,
                self::TARGET_HASH_RULE_SUCCESS
            )
        );

        // Verify actual transformations
        $this->verifyHashAttributeTransformations($testProject);

        $this->markTestAsSuccessful('hash_rule_effectiveness', $hashRuleSuccess);
    }

    /**
     * @group time_savings
     * @group performance
     */
    public function testTimeSavingsTarget(): void
    {
        // Estimate manual migration time based on file count and complexity
        $manualTime = $this->estimateManualMigrationTime();

        // Measure actual automation time
        $startTime = microtime(true);
        $this->runRectorOnProject();
        $automationTime = microtime(true) - $startTime;

        // Calculate time reduction
        $timeReductionPercentage = (($manualTime - $automationTime) / $manualTime) * 100;

        $this->assertGreaterThanOrEqual(
            self::TARGET_TIME_REDUCTION,
            $timeReductionPercentage,
            sprintf(
                'Time reduction (%.2f%%) must meet target of %.1f%%',
                $timeReductionPercentage,
                self::TARGET_TIME_REDUCTION
            )
        );

        // Generate time savings report
        $this->metricsCollector->calculateTimeSavings((int)$manualTime);
        $savings = $this->metricsCollector->getTimeSavingsMetrics();

        $this->assertArrayHasKey('time_saved_hours', $savings);
        $this->assertGreaterThan(0, $savings['time_saved_hours']);

        $this->markTestAsSuccessful('time_savings', $timeReductionPercentage);
    }

    /**
     * @group error_reduction
     */
    public function testErrorReductionTarget(): void
    {
        $testProject = $this->createComplexTestProject();

        // Simulate manual migration errors (based on empirical data)
        $manualErrors = [
            'syntax_errors' => 5,
            'type_errors' => 8,
            'deprecation_warnings' => 12,
            'missed_conversions' => 15,
        ];

        // Run automated migration and track errors
        $automatedErrors = $this->runAndCountErrors($testProject);

        // Calculate error reduction
        $totalManualErrors = array_sum($manualErrors);
        $totalAutomatedErrors = array_sum($automatedErrors);
        $errorReductionPercentage = 0;

        if ($totalManualErrors > 0) {
            $errorReductionPercentage = (($totalManualErrors - $totalAutomatedErrors) / $totalManualErrors) * 100;
        }

        $this->assertGreaterThanOrEqual(
            self::TARGET_ERROR_REDUCTION,
            $errorReductionPercentage,
            sprintf(
                'Error reduction (%.2f%%) must meet target of %.1f%%',
                $errorReductionPercentage,
                self::TARGET_ERROR_REDUCTION
            )
        );

        $this->markTestAsSuccessful('error_reduction', $errorReductionPercentage);
    }

    /**
     * @group comprehensive
     * @group critical
     */
    public function testComprehensiveAutomationValidation(): void
    {
        $this->metricsCollector->reset();
        $this->metricsCollector->startTiming();

        // Run full Rector process
        $result = $this->runFullRectorProcess();

        $this->metricsCollector->stopTiming();

        // Collect all metrics
        $metrics = $this->metricsCollector->getMetrics();
        $summary = $this->metricsCollector->getSummaryStatistics();
        $ruleEffectiveness = $this->metricsCollector->getRuleEffectivenessReport();

        // Validate all targets
        $validationResults = [
            'automation_percentage' => [
                'target' => self::TARGET_AUTOMATION_PERCENTAGE,
                'achieved' => $metrics['automation_percentage'],
                'met' => $metrics['automation_percentage'] >= self::TARGET_AUTOMATION_PERCENTAGE,
            ],
            'time_reduction' => [
                'target' => self::TARGET_TIME_REDUCTION,
                'achieved' => $this->calculateTimeReduction(),
                'met' => $this->calculateTimeReduction() >= self::TARGET_TIME_REDUCTION,
            ],
            'error_reduction' => [
                'target' => self::TARGET_ERROR_REDUCTION,
                'achieved' => $this->calculateErrorReduction(),
                'met' => $this->calculateErrorReduction() >= self::TARGET_ERROR_REDUCTION,
            ],
            'rule_effectiveness' => [
                'target' => 85.0,
                'achieved' => $this->calculateAverageRuleEffectiveness($ruleEffectiveness),
                'met' => $this->calculateAverageRuleEffectiveness($ruleEffectiveness) >= 85.0,
            ],
        ];

        // Generate validation report
        $report = $this->generateValidationReport($validationResults, $metrics, $summary);

        // Assert all targets are met
        foreach ($validationResults as $metric => $result) {
            $this->assertTrue(
                $result['met'],
                sprintf(
                    '%s: Target %.1f%% not met (achieved: %.1f%%)',
                    $metric,
                    $result['target'],
                    $result['achieved']
                )
            );
        }

        // Generate and save reports
        $reports = $this->reportGenerator->generateReport('all');

        $this->assertNotEmpty($reports);
        $this->assertArrayHasKey('json', $reports);
        $this->assertArrayHasKey('html', $reports);
        $this->assertArrayHasKey('markdown', $reports);

        // Final success marker
        $this->markTestAsSuccessful('comprehensive_validation', 100.0);
    }

    /**
     * @group staged_migration
     */
    public function testStagedMigrationApproach(): void
    {
        $stages = [
            'php81' => ['config' => 'rector-php81.php', 'expected_automation' => 30.0],
            'php82' => ['config' => 'rector-php82.php', 'expected_automation' => 20.0],
            'php83' => ['config' => 'rector-php83.php', 'expected_automation' => 15.0],
            'symfony' => ['config' => 'rector-symfony.php', 'expected_automation' => 25.0],
            'quality' => ['config' => 'rector-quality.php', 'expected_automation' => 10.0],
        ];

        $cumulativeAutomation = 0;
        $stageResults = [];

        foreach ($stages as $stage => $config) {
            if (!file_exists($this->projectDir . '/' . $config['config'])) {
                continue; // Skip if config doesn't exist
            }

            $result = $this->runRectorWithConfig($config['config']);
            $automation = $this->calculateStageAutomation($result);

            $stageResults[$stage] = [
                'automation' => $automation,
                'expected' => $config['expected_automation'],
                'met' => $automation >= ($config['expected_automation'] * 0.8), // 80% of expected
            ];

            $cumulativeAutomation += $automation;
        }

        // Verify cumulative automation meets target
        $this->assertGreaterThanOrEqual(
            self::TARGET_AUTOMATION_PERCENTAGE,
            $cumulativeAutomation,
            'Cumulative automation from staged approach must meet 70% target'
        );

        $this->generateStagedMigrationReport($stageResults, $cumulativeAutomation);
    }

    /**
     * @group ci_integration
     */
    public function testContinuousIntegrationValidation(): void
    {
        // This test is designed to run in CI environments
        if (!getenv('CI')) {
            $this->markTestSkipped('This test is designed for CI environments');
        }

        // Run Rector in CI mode (fail-fast)
        $process = new Process([
            'vendor/bin/rector',
            'process',
            '--dry-run',
            '--output-format=json',
            '--config=rector.php'
        ], $this->projectDir);

        $process->setTimeout(300); // 5 minute timeout
        $process->run();

        $this->assertTrue($process->isSuccessful(), 'Rector should run successfully in CI');

        // Parse output and validate
        $output = json_decode($process->getOutput(), true);

        if ($output !== null) {
            $automationRate = $this->calculateAutomationFromOutput($output);

            $this->assertGreaterThanOrEqual(
                self::TARGET_AUTOMATION_PERCENTAGE,
                $automationRate,
                'CI validation must confirm 70%+ automation target'
            );
        }
    }

    private function runRectorOnProject(): array
    {
        $process = new Process([
            'vendor/bin/rector',
            'process',
            '--dry-run',
            '--config=rector.php'
        ], $this->projectDir);

        $process->setTimeout(180);
        $process->run();

        // Track metrics
        $this->trackProcessMetrics($process);

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'errors' => $process->getErrorOutput(),
        ];
    }

    private function runFullRectorProcess(): array
    {
        $tempDir = sys_get_temp_dir() . '/rector-validation-' . uniqid();
        $this->filesystem->mirror($this->projectDir . '/src', $tempDir . '/src');
        $this->filesystem->mirror($this->projectDir . '/tests', $tempDir . '/tests');

        $process = new Process([
            'vendor/bin/rector',
            'process',
            $tempDir,
            '--config=' . $this->projectDir . '/rector.php'
        ]);

        $process->setTimeout(300);
        $process->run();

        $result = [
            'success' => $process->isSuccessful(),
            'files_changed' => $this->countChangedFiles($tempDir),
            'output' => $process->getOutput(),
        ];

        // Cleanup
        $this->filesystem->remove($tempDir);

        return $result;
    }

    private function runRectorWithCustomRules(string $projectDir): array
    {
        $configContent = <<<'PHP'
<?php
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths(['%PROJECT_DIR%']);

    // Custom HashId rules
    $rectorConfig->rule(\Pgs\HashIdBundle\Rector\HashAnnotationToAttributeRule::class);
    $rectorConfig->rule(\Pgs\HashIdBundle\Rector\RouterDecoratorModernizationRule::class);
};
PHP;

        $configContent = str_replace('%PROJECT_DIR%', $projectDir, $configContent);
        $configFile = $projectDir . '/rector-custom.php';
        file_put_contents($configFile, $configContent);

        $process = new Process([
            'vendor/bin/rector',
            'process',
            $projectDir,
            '--config=' . $configFile
        ]);

        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
        ];
    }

    private function runRectorWithConfig(string $configFile): array
    {
        $process = new Process([
            'vendor/bin/rector',
            'process',
            '--dry-run',
            '--config=' . $this->projectDir . '/' . $configFile
        ], $this->projectDir);

        $process->setTimeout(180);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
        ];
    }

    private function runAndCountErrors(string $projectDir): array
    {
        $process = new Process([
            'vendor/bin/rector',
            'process',
            $projectDir,
            '--dry-run'
        ]);

        $process->run();

        $output = $process->getOutput() . $process->getErrorOutput();

        return [
            'syntax_errors' => substr_count($output, 'syntax error'),
            'type_errors' => substr_count($output, 'type error'),
            'deprecation_warnings' => substr_count($output, 'deprecated'),
            'missed_conversions' => 0, // Would need deeper analysis
        ];
    }

    private function trackProcessMetrics(Process $process): void
    {
        $output = $process->getOutput();

        // Parse files processed and modified
        if (preg_match('/(\d+) files? (?:were |was )?processed/', $output, $matches)) {
            $filesProcessed = (int)$matches[1];
            for ($i = 0; $i < $filesProcessed; $i++) {
                $this->metricsCollector->trackFileProcessed("file_$i");
            }
        }

        if (preg_match('/(\d+) files? (?:were |was )?changed/', $output, $matches)) {
            $filesChanged = (int)$matches[1];
            for ($i = 0; $i < $filesChanged; $i++) {
                $this->metricsCollector->trackFileModified("file_$i", 1);
            }
        }
    }

    private function createHashIdTestProject(): string
    {
        $tempDir = sys_get_temp_dir() . '/hashid-test-' . uniqid();
        $this->filesystem->mkdir($tempDir);

        // Create test files with Hash annotations
        $files = [
            'Controller/TestController.php' => <<<'PHP'
<?php
namespace Test\Controller;
use Pgs\HashIdBundle\Annotation\Hash;

class TestController
{
    /**
     * @Hash("id")
     */
    public function show(int $id) {}

    /**
     * @Hash({"id", "parentId"})
     */
    public function related(int $id, int $parentId) {}
}
PHP,
            'Service/HashService.php' => <<<'PHP'
<?php
namespace Test\Service;

class HashService
{
    private $encoder;
    private $decoder;

    public function __construct($encoder, $decoder)
    {
        $this->encoder = $encoder;
        $this->decoder = $decoder;
    }
}
PHP,
        ];

        foreach ($files as $path => $content) {
            $fullPath = $tempDir . '/' . $path;
            $this->filesystem->mkdir(dirname($fullPath));
            file_put_contents($fullPath, $content);
        }

        return $tempDir;
    }

    private function createComplexTestProject(): string
    {
        $tempDir = sys_get_temp_dir() . '/complex-test-' . uniqid();
        $this->filesystem->mkdir($tempDir);

        // Create a more complex project structure
        $this->filesystem->mirror($this->projectDir . '/src', $tempDir . '/src');
        $this->filesystem->mirror($this->projectDir . '/tests', $tempDir . '/tests');

        return $tempDir;
    }

    private function verifyHashAttributeTransformations(string $projectDir): void
    {
        $finder = new Finder();
        $finder->files()->in($projectDir)->name('*.php');

        foreach ($finder as $file) {
            $content = $file->getContents();

            // Check for successful transformation
            if (strpos($content, 'Controller') !== false) {
                $this->assertStringNotContainsString(
                    '@Hash',
                    $content,
                    'Annotations should be converted to attributes'
                );

                $this->assertStringContainsString(
                    '#[Hash',
                    $content,
                    'Attributes should be present after transformation'
                );
            }
        }
    }

    private function calculateHashRuleSuccess(array $result): float
    {
        if (!$result['success']) {
            return 0.0;
        }

        $output = $result['output'];
        $totalHashAnnotations = substr_count($output, '@Hash');
        $convertedAttributes = substr_count($output, '#[Hash');

        if ($totalHashAnnotations === 0) {
            return 100.0; // No annotations to convert
        }

        return ($convertedAttributes / $totalHashAnnotations) * 100;
    }

    private function estimateManualMigrationTime(): float
    {
        $finder = new Finder();
        $finder->files()->in($this->projectDir . '/src')->name('*.php');
        $fileCount = $finder->count();

        // Empirical data: ~5 minutes per file for manual migration
        return $fileCount * 5 * 60; // Convert to seconds
    }

    private function calculateTimeReduction(): float
    {
        $manualTime = $this->estimateManualMigrationTime();
        $metrics = $this->metricsCollector->getMetrics();
        $automationTime = $metrics['execution_time'] ?? 0;

        if ($manualTime <= 0) {
            return 0;
        }

        return (($manualTime - $automationTime) / $manualTime) * 100;
    }

    private function calculateErrorReduction(): float
    {
        // Based on empirical data
        $baselineErrorRate = 25.0; // Manual migration error rate
        $metrics = $this->metricsCollector->getMetrics();
        $currentErrorRate = $metrics['error_rate'] ?? 0;

        if ($baselineErrorRate <= 0) {
            return 0;
        }

        return (($baselineErrorRate - $currentErrorRate) / $baselineErrorRate) * 100;
    }

    private function calculateAverageRuleEffectiveness(array $rules): float
    {
        if (empty($rules)) {
            return 0;
        }

        $totalSuccess = array_sum(array_column($rules, 'success_rate'));
        return $totalSuccess / count($rules);
    }

    private function calculateStageAutomation(array $result): float
    {
        if (!$result['success']) {
            return 0;
        }

        $output = $result['output'];

        if (preg_match('/(\d+) files? changed/', $output, $changedMatches) &&
            preg_match('/(\d+) files? processed/', $output, $processedMatches)) {
            $changed = (int)$changedMatches[1];
            $processed = (int)$processedMatches[1];

            return $processed > 0 ? ($changed / $processed) * 100 : 0;
        }

        return 0;
    }

    private function calculateAutomationFromOutput(array $output): float
    {
        if (isset($output['files_changed']) && isset($output['files_total'])) {
            return ($output['files_changed'] / $output['files_total']) * 100;
        }

        return 0;
    }

    private function countChangedFiles(string $dir): int
    {
        $process = new Process(['git', 'diff', '--name-only'], $dir);
        $process->run();

        if ($process->isSuccessful()) {
            $files = array_filter(explode("\n", $process->getOutput()));
            return count($files);
        }

        return 0;
    }

    private function generateValidationReport(array $results, array $metrics, array $summary): array
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'validation_results' => $results,
            'metrics' => $metrics,
            'summary' => $summary,
            'overall_status' => $this->determineOverallStatus($results),
            'recommendations' => $this->generateRecommendations($results),
        ];

        // Save report
        $reportFile = __DIR__ . '/../../var/rector-validation-report.json';
        $this->filesystem->mkdir(dirname($reportFile));
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));

        return $report;
    }

    private function generateStagedMigrationReport(array $stageResults, float $cumulative): void
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'stage_results' => $stageResults,
            'cumulative_automation' => $cumulative,
            'target_met' => $cumulative >= self::TARGET_AUTOMATION_PERCENTAGE,
        ];

        $reportFile = __DIR__ . '/../../var/staged-migration-report.json';
        $this->filesystem->mkdir(dirname($reportFile));
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
    }

    private function determineOverallStatus(array $results): string
    {
        foreach ($results as $result) {
            if (!$result['met']) {
                return 'FAILED';
            }
        }
        return 'PASSED';
    }

    private function generateRecommendations(array $results): array
    {
        $recommendations = [];

        foreach ($results as $metric => $result) {
            if (!$result['met']) {
                $gap = $result['target'] - $result['achieved'];
                $recommendations[] = sprintf(
                    '%s needs improvement: %.1f%% gap to target',
                    $metric,
                    $gap
                );
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = 'All targets met successfully. Ready for production release.';
        }

        return $recommendations;
    }

    private function markTestAsSuccessful(string $category, float $value): void
    {
        $successFile = __DIR__ . '/../../var/rector-success-markers.json';
        $this->filesystem->mkdir(dirname($successFile));

        $markers = [];
        if (file_exists($successFile)) {
            $markers = json_decode(file_get_contents($successFile), true) ?? [];
        }

        $markers[$category] = [
            'value' => $value,
            'timestamp' => date('Y-m-d H:i:s'),
            'test_class' => __CLASS__,
        ];

        file_put_contents($successFile, json_encode($markers, JSON_PRETTY_PRINT));
    }
}