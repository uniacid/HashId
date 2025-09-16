<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Rector;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Rector\MetricsCollector;
use Symfony\Component\Process\Process;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Stopwatch\Stopwatch;

class CustomRulePerformanceTest extends TestCase
{
    private string $testProjectDir;
    private MetricsCollector $metricsCollector;
    private Stopwatch $stopwatch;

    private const TARGET_HASH_RULE_SUCCESS = 90.0;
    private const TARGET_PROMOTION_RULE_SUCCESS = 85.0;
    private const TARGET_READONLY_RULE_SUCCESS = 85.0;
    private const TARGET_TYPE_RULE_SUCCESS = 80.0;

    protected function setUp(): void
    {
        $this->testProjectDir = sys_get_temp_dir() . '/hashid-custom-rule-performance-' . uniqid();
        if (!is_dir($this->testProjectDir)) {
            mkdir($this->testProjectDir, 0777, true);
        }

        $this->metricsCollector = new MetricsCollector();
        $this->stopwatch = new Stopwatch();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testProjectDir)) {
            $this->removeDirectory($this->testProjectDir);
        }
    }

    public function testHashAnnotationToAttributeRulePerformance(): void
    {
        $this->createHashAnnotationTestProject();

        $this->stopwatch->start('hash_rule');
        $metrics = $this->runCustomRule('HashAnnotationToAttributeRule');
        $event = $this->stopwatch->stop('hash_rule');

        // Performance assertions
        $this->assertLessThan(1000, $event->getDuration(),
            'Hash annotation rule should execute in under 1 second for test project');
        $this->assertLessThan(50 * 1024 * 1024, $event->getMemory(),
            'Hash annotation rule should use less than 50MB of memory');

        // Success rate assertions
        $successRate = $this->calculateSuccessRate($metrics);
        $this->assertGreaterThanOrEqual(self::TARGET_HASH_RULE_SUCCESS, $successRate,
            sprintf('Hash annotation to attribute conversion should achieve at least %.1f%% success rate',
                self::TARGET_HASH_RULE_SUCCESS));

        // Document performance
        $this->documentRulePerformance('HashAnnotationToAttributeRule', $metrics, $event);

        // Validate transformations
        $this->validateHashAttributeTransformations();
    }

    public function testConstructorPropertyPromotionRulePerformance(): void
    {
        $this->createConstructorTestProject();

        $this->stopwatch->start('promotion_rule');
        $metrics = $this->runCustomRule('ConstructorPropertyPromotionRule');
        $event = $this->stopwatch->stop('promotion_rule');

        $successRate = $this->calculateSuccessRate($metrics);
        $this->assertGreaterThanOrEqual(self::TARGET_PROMOTION_RULE_SUCCESS, $successRate,
            sprintf('Constructor property promotion should achieve at least %.1f%% success rate',
                self::TARGET_PROMOTION_RULE_SUCCESS));

        $this->assertArrayHasKey('properties_promoted', $metrics);
        $this->assertGreaterThan(0, $metrics['properties_promoted'],
            'Should successfully promote at least some properties');

        $this->documentRulePerformance('ConstructorPropertyPromotionRule', $metrics, $event);
    }

    public function testReadOnlyPropertyRulePerformance(): void
    {
        $this->createReadOnlyTestProject();

        $this->stopwatch->start('readonly_rule');
        $metrics = $this->runCustomRule('ReadOnlyPropertyRule');
        $event = $this->stopwatch->stop('readonly_rule');

        $successRate = $this->calculateSuccessRate($metrics);
        $this->assertGreaterThanOrEqual(self::TARGET_READONLY_RULE_SUCCESS, $successRate,
            sprintf('Readonly property rule should achieve at least %.1f%% success rate',
                self::TARGET_READONLY_RULE_SUCCESS));

        $this->documentRulePerformance('ReadOnlyPropertyRule', $metrics, $event);
    }

    public function testTypeDeclarationRulePerformance(): void
    {
        $this->createTypedPropertyTestProject();

        $this->stopwatch->start('type_rule');
        $metrics = $this->runCustomRule('TypeDeclarationRule');
        $event = $this->stopwatch->stop('type_rule');

        $successRate = $this->calculateSuccessRate($metrics);
        $this->assertGreaterThanOrEqual(self::TARGET_TYPE_RULE_SUCCESS, $successRate,
            sprintf('Type declaration rule should achieve at least %.1f%% success rate',
                self::TARGET_TYPE_RULE_SUCCESS));

        $this->documentRulePerformance('TypeDeclarationRule', $metrics, $event);
    }

    public function testRouterDecoratorModernizationRulePerformance(): void
    {
        $this->createRouterDecoratorTestProject();

        $this->stopwatch->start('router_rule');
        $metrics = $this->runCustomRule('RouterDecoratorModernizationRule');
        $event = $this->stopwatch->stop('router_rule');

        $this->assertArrayHasKey('decorators_modernized', $metrics);
        $this->assertGreaterThan(0, $metrics['decorators_modernized'],
            'Should modernize router decorator patterns');

        $this->documentRulePerformance('RouterDecoratorModernizationRule', $metrics, $event);
    }

    public function testAllCustomRulesCombinedPerformance(): void
    {
        $this->createCompleteTestProject();

        $this->stopwatch->start('all_rules');
        $metrics = $this->runAllCustomRules();
        $event = $this->stopwatch->stop('all_rules');

        // Performance benchmarks for combined rules
        $filesPerSecond = $metrics['files_processed'] / ($event->getDuration() / 1000);
        $this->assertGreaterThan(5, $filesPerSecond,
            'Combined rules should process at least 5 files per second');

        $memoryUsageMB = $event->getMemory() / 1024 / 1024;
        $this->assertLessThan(128, $memoryUsageMB,
            'Combined rules should use less than 128MB of memory');

        // Generate comprehensive performance report
        $this->generateComprehensivePerformanceReport($metrics, $event);
    }

    public function testRulePerformanceUnderLoad(): void
    {
        $fileCounts = [10, 50, 100, 200];
        $performanceData = [];

        foreach ($fileCounts as $count) {
            $this->setUp(); // Reset for each test
            $this->createLargeTestProject($count);

            $this->stopwatch->start("load_test_{$count}");
            $metrics = $this->runAllCustomRules();
            $event = $this->stopwatch->stop("load_test_{$count}");

            $performanceData[$count] = [
                'files' => $count,
                'duration_ms' => $event->getDuration(),
                'memory_mb' => $event->getMemory() / 1024 / 1024,
                'files_per_second' => $count / ($event->getDuration() / 1000),
                'success_rate' => $this->calculateSuccessRate($metrics),
            ];

            $this->tearDown();
        }

        // Analyze performance scaling
        $this->analyzePerformanceScaling($performanceData);

        // Assert linear or better scaling
        $scalingFactor = $performanceData[200]['duration_ms'] / $performanceData[10]['duration_ms'];
        $this->assertLessThan(25, $scalingFactor,
            'Performance should scale sub-linearly (better than O(n))');
    }

    public function testRuleAccuracyMetrics(): void
    {
        $testCases = $this->getAccuracyTestCases();
        $accuracyMetrics = [];

        foreach ($testCases as $ruleName => $cases) {
            $correctTransformations = 0;
            $totalCases = count($cases);

            foreach ($cases as $case) {
                $this->createTestFile('test.php', $case['input']);
                $this->runCustomRule($ruleName);

                $result = file_get_contents($this->testProjectDir . '/test.php');
                if ($this->compareTransformations($result, $case['expected'])) {
                    $correctTransformations++;
                }
            }

            $accuracyMetrics[$ruleName] = [
                'accuracy_percentage' => ($correctTransformations / $totalCases) * 100,
                'correct_transformations' => $correctTransformations,
                'total_cases' => $totalCases,
            ];
        }

        $this->saveAccuracyMetrics($accuracyMetrics);

        // Assert minimum accuracy for each rule
        foreach ($accuracyMetrics as $ruleName => $metrics) {
            $this->assertGreaterThanOrEqual(95.0, $metrics['accuracy_percentage'],
                "Rule {$ruleName} should have at least 95% accuracy");
        }
    }

    public function testRuleEffectivenessComparison(): void
    {
        $rules = [
            'HashAnnotationToAttributeRule' => [],
            'ConstructorPropertyPromotionRule' => [],
            'ReadOnlyPropertyRule' => [],
            'TypeDeclarationRule' => [],
            'RouterDecoratorModernizationRule' => [],
        ];

        $this->createCompleteTestProject();

        foreach ($rules as $ruleName => &$ruleMetrics) {
            $this->metricsCollector->reset();
            $this->metricsCollector->startTiming();

            $result = $this->runCustomRule($ruleName);

            $this->metricsCollector->stopTiming();
            $metrics = $this->metricsCollector->getMetrics();

            $ruleMetrics = [
                'success_rate' => $this->calculateSuccessRate($result),
                'execution_time' => $metrics['execution_time'],
                'files_processed' => $result['files_processed'] ?? 0,
                'changes_made' => $result['changes_made'] ?? 0,
                'errors' => $result['errors'] ?? [],
            ];
        }

        // Generate comparison report
        $this->generateRuleComparisonReport($rules);

        // Find most and least effective rules
        $successRates = array_column($rules, 'success_rate');
        $mostEffective = array_search(max($successRates), $successRates);
        $leastEffective = array_search(min($successRates), $successRates);

        $this->assertNotEquals($mostEffective, $leastEffective,
            'There should be variation in rule effectiveness');
    }

    public function testTimingMetricsGeneration(): void
    {
        $timingData = [];
        $iterations = 5; // Run each rule multiple times for average

        $rules = [
            'HashAnnotationToAttributeRule',
            'ConstructorPropertyPromotionRule',
            'ReadOnlyPropertyRule',
            'TypeDeclarationRule',
        ];

        foreach ($rules as $ruleName) {
            $timings = [];

            for ($i = 0; $i < $iterations; $i++) {
                $this->createMixedTestProject();

                $startTime = microtime(true);
                $this->runCustomRule($ruleName);
                $endTime = microtime(true);

                $timings[] = ($endTime - $startTime) * 1000; // Convert to milliseconds
            }

            $timingData[$ruleName] = [
                'average_ms' => array_sum($timings) / count($timings),
                'min_ms' => min($timings),
                'max_ms' => max($timings),
                'std_dev' => $this->calculateStandardDeviation($timings),
            ];
        }

        $this->saveTimingMetrics($timingData);

        // Assert reasonable timing consistency
        foreach ($timingData as $ruleName => $data) {
            $variationCoefficient = $data['std_dev'] / $data['average_ms'];
            $this->assertLessThan(0.3, $variationCoefficient,
                "Rule {$ruleName} should have consistent performance (CV < 30%)");
        }
    }

    private function runCustomRule(string $ruleName): array
    {
        $configFile = $this->createCustomRuleConfig($ruleName);

        $process = new Process([
            'vendor/bin/rector',
            'process',
            $this->testProjectDir,
            '--config=' . $configFile,
            '--dry-run',
            '--output-format=json'
        ]);

        $process->run();

        $metrics = $this->parseRectorOutput($process->getOutput());

        if (!$process->isSuccessful()) {
            $metrics['errors'][] = $process->getErrorOutput();
        }

        // Track in MetricsCollector for report generation
        $this->metricsCollector->trackRuleApplied(
            $ruleName,
            $this->testProjectDir,
            $process->isSuccessful()
        );

        return $metrics;
    }

    private function runAllCustomRules(): array
    {
        $rules = [
            'HashAnnotationToAttributeRule',
            'ConstructorPropertyPromotionRule',
            'ReadOnlyPropertyRule',
            'TypeDeclarationRule',
            'RouterDecoratorModernizationRule',
        ];

        $configFile = $this->createCustomRuleConfig(...$rules);

        $process = new Process([
            'vendor/bin/rector',
            'process',
            $this->testProjectDir,
            '--config=' . $configFile,
            '--dry-run',
            '--output-format=json'
        ]);

        $process->run();

        return $this->parseRectorOutput($process->getOutput());
    }

    private function parseRectorOutput(string $output): array
    {
        $metrics = [
            'files_processed' => 0,
            'files_modified' => 0,
            'changes_made' => 0,
            'properties_promoted' => 0,
            'decorators_modernized' => 0,
            'errors' => [],
        ];

        // Try to parse JSON output
        if ($jsonStart = strpos($output, '{')) {
            $jsonString = substr($output, $jsonStart);
            $jsonData = json_decode($jsonString, true);

            if ($jsonData !== null) {
                $metrics = array_merge($metrics, $jsonData);
            }
        }

        // Parse text output as fallback
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (preg_match('/(\d+) files? (?:were |was )?processed/', $line, $matches)) {
                $metrics['files_processed'] = (int)$matches[1];
            }
            if (preg_match('/(\d+) files? (?:were |was )?changed/', $line, $matches)) {
                $metrics['files_modified'] = (int)$matches[1];
            }
            if (preg_match('/(\d+) (?:changes?|modifications?)/', $line, $matches)) {
                $metrics['changes_made'] = (int)$matches[1];
            }
        }

        return $metrics;
    }

    private function calculateSuccessRate(array $metrics): float
    {
        if ($metrics['files_processed'] === 0) {
            return 0.0;
        }

        return ($metrics['files_modified'] / $metrics['files_processed']) * 100;
    }

    private function documentRulePerformance(string $ruleName, array $metrics, $event): void
    {
        $documentation = [
            'rule_name' => $ruleName,
            'timestamp' => date('Y-m-d H:i:s'),
            'performance' => [
                'duration_ms' => $event->getDuration(),
                'memory_mb' => $event->getMemory() / 1024 / 1024,
                'files_per_second' => $metrics['files_processed'] / ($event->getDuration() / 1000),
            ],
            'metrics' => $metrics,
            'success_rate' => $this->calculateSuccessRate($metrics),
        ];

        $this->savePerformanceDocumentation($ruleName, $documentation);
    }

    private function savePerformanceDocumentation(string $ruleName, array $data): void
    {
        $dir = __DIR__ . '/../../var/rector-performance';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filename = sprintf('%s/%s-performance-%s.json',
            $dir,
            strtolower(str_replace('Rule', '', $ruleName)),
            date('Y-m-d-His')
        );

        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function generateComprehensivePerformanceReport(array $metrics, $event): void
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => [
                'total_duration_ms' => $event->getDuration(),
                'total_memory_mb' => $event->getMemory() / 1024 / 1024,
                'files_processed' => $metrics['files_processed'],
                'files_modified' => $metrics['files_modified'],
                'overall_success_rate' => $this->calculateSuccessRate($metrics),
            ],
            'rules_effectiveness' => $this->metricsCollector->getRuleEffectivenessReport(),
            'time_savings' => $this->metricsCollector->getTimeSavingsMetrics(),
            'meets_targets' => [
                'hash_rule' => $this->meetsTarget('HashAnnotationToAttributeRule', self::TARGET_HASH_RULE_SUCCESS),
                'promotion_rule' => $this->meetsTarget('ConstructorPropertyPromotionRule', self::TARGET_PROMOTION_RULE_SUCCESS),
                'readonly_rule' => $this->meetsTarget('ReadOnlyPropertyRule', self::TARGET_READONLY_RULE_SUCCESS),
                'type_rule' => $this->meetsTarget('TypeDeclarationRule', self::TARGET_TYPE_RULE_SUCCESS),
            ],
        ];

        $dir = __DIR__ . '/../../var/rector-performance';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Save as JSON
        file_put_contents(
            $dir . '/comprehensive-performance-report.json',
            json_encode($report, JSON_PRETTY_PRINT)
        );

        // Save as Markdown
        file_put_contents(
            $dir . '/comprehensive-performance-report.md',
            $this->generateMarkdownReport($report)
        );
    }

    private function generateMarkdownReport(array $report): string
    {
        $markdown = "# Custom Rule Performance Report\n\n";
        $markdown .= "Generated: {$report['timestamp']}\n\n";

        $markdown .= "## Summary\n\n";
        $markdown .= "| Metric | Value |\n";
        $markdown .= "|--------|-------|\n";
        foreach ($report['summary'] as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            $formattedValue = is_float($value) ? sprintf('%.2f', $value) : $value;
            $markdown .= "| {$label} | {$formattedValue} |\n";
        }

        $markdown .= "\n## Target Achievement\n\n";
        foreach ($report['meets_targets'] as $rule => $met) {
            $status = $met ? '✅' : '❌';
            $ruleName = ucwords(str_replace('_', ' ', $rule));
            $markdown .= "- {$status} {$ruleName}\n";
        }

        $markdown .= "\n## Rule Effectiveness\n\n";
        $markdown .= "| Rule | Success Rate | Applications |\n";
        $markdown .= "|------|-------------|-------------|\n";
        foreach ($report['rules_effectiveness'] as $rule) {
            $markdown .= sprintf("| %s | %.1f%% | %d |\n",
                basename(str_replace('\\', '/', $rule['rule_name'])),
                $rule['success_rate'],
                $rule['total_applications']
            );
        }

        return $markdown;
    }

    private function meetsTarget(string $ruleName, float $targetPercentage): bool
    {
        $metrics = $this->metricsCollector->getMetrics();

        if (!isset($metrics['rules_applied'][$ruleName])) {
            return false;
        }

        return $metrics['rules_applied'][$ruleName]['success_rate'] >= $targetPercentage;
    }

    private function analyzePerformanceScaling(array $performanceData): void
    {
        $report = "# Performance Scaling Analysis\n\n";
        $report .= "| File Count | Duration (ms) | Memory (MB) | Files/sec | Success Rate |\n";
        $report .= "|------------|---------------|-------------|-----------|-------------|\n";

        foreach ($performanceData as $data) {
            $report .= sprintf("| %d | %.2f | %.2f | %.2f | %.1f%% |\n",
                $data['files'],
                $data['duration_ms'],
                $data['memory_mb'],
                $data['files_per_second'],
                $data['success_rate']
            );
        }

        $dir = __DIR__ . '/../../var/rector-performance';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($dir . '/scaling-analysis.md', $report);
    }

    private function generateRuleComparisonReport(array $rules): void
    {
        $report = "# Rule Effectiveness Comparison\n\n";
        $report .= "| Rule | Success Rate | Execution Time (s) | Files Processed | Changes Made |\n";
        $report .= "|------|-------------|-------------------|-----------------|-------------|\n";

        foreach ($rules as $ruleName => $metrics) {
            $report .= sprintf("| %s | %.1f%% | %.3f | %d | %d |\n",
                basename(str_replace('\\', '/', $ruleName)),
                $metrics['success_rate'] ?? 0,
                $metrics['execution_time'] ?? 0,
                $metrics['files_processed'] ?? 0,
                $metrics['changes_made'] ?? 0
            );
        }

        $dir = __DIR__ . '/../../var/rector-performance';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($dir . '/rule-comparison.md', $report);
    }

    private function saveAccuracyMetrics(array $metrics): void
    {
        $dir = __DIR__ . '/../../var/rector-performance';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents(
            $dir . '/accuracy-metrics.json',
            json_encode($metrics, JSON_PRETTY_PRINT)
        );
    }

    private function saveTimingMetrics(array $metrics): void
    {
        $dir = __DIR__ . '/../../var/rector-performance';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents(
            $dir . '/timing-metrics.json',
            json_encode($metrics, JSON_PRETTY_PRINT)
        );
    }

    private function calculateStandardDeviation(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $variance = 0;

        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }

        $variance /= count($values);
        return sqrt($variance);
    }

    private function compareTransformations(string $actual, string $expected): bool
    {
        // Normalize whitespace for comparison
        $actual = preg_replace('/\s+/', ' ', trim($actual));
        $expected = preg_replace('/\s+/', ' ', trim($expected));

        return $actual === $expected;
    }

    private function validateHashAttributeTransformations(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->testProjectDir)->name('*.php');

        foreach ($finder as $file) {
            $content = $file->getContents();

            // Check that annotations are converted to attributes
            if (strpos($content, '@Hash') !== false) {
                $this->fail("File {$file->getFilename()} still contains @Hash annotations");
            }

            // Verify attribute syntax is correct
            if (strpos($content, '#[Hash') !== false) {
                $this->assertMatchesRegularExpression(
                    '/#\[Hash\([\'"][a-zA-Z_]+[\'"]\)\]/',
                    $content,
                    "Hash attributes should have valid syntax"
                );
            }
        }
    }

    private function createHashAnnotationTestProject(): void
    {
        $files = [
            'SimpleHashController.php' => $this->getHashAnnotationFile('simple'),
            'MultipleHashController.php' => $this->getHashAnnotationFile('multiple'),
            'ComplexHashController.php' => $this->getHashAnnotationFile('complex'),
        ];

        $this->createFiles($files);
    }

    private function createConstructorTestProject(): void
    {
        $files = [
            'BasicService.php' => $this->getConstructorFile('basic'),
            'ComplexService.php' => $this->getConstructorFile('complex'),
            'MixedService.php' => $this->getConstructorFile('mixed'),
        ];

        $this->createFiles($files);
    }

    private function createReadOnlyTestProject(): void
    {
        $files = [
            'Entity.php' => $this->getReadOnlyFile('entity'),
            'ValueObject.php' => $this->getReadOnlyFile('value_object'),
            'DTO.php' => $this->getReadOnlyFile('dto'),
        ];

        $this->createFiles($files);
    }

    private function createTypedPropertyTestProject(): void
    {
        $files = [
            'UntypedClass.php' => $this->getTypedPropertyFile('untyped'),
            'PartiallyTyped.php' => $this->getTypedPropertyFile('partial'),
            'DocblockTyped.php' => $this->getTypedPropertyFile('docblock'),
        ];

        $this->createFiles($files);
    }

    private function createRouterDecoratorTestProject(): void
    {
        $files = [
            'RouterDecorator.php' => $this->getRouterDecoratorFile(),
            'HasherFactory.php' => $this->getHasherFactoryFile(),
        ];

        $this->createFiles($files);
    }

    private function createMixedTestProject(): void
    {
        $this->createHashAnnotationTestProject();
        $this->createConstructorTestProject();
        $this->createReadOnlyTestProject();
    }

    private function createCompleteTestProject(): void
    {
        $this->createHashAnnotationTestProject();
        $this->createConstructorTestProject();
        $this->createReadOnlyTestProject();
        $this->createTypedPropertyTestProject();
        $this->createRouterDecoratorTestProject();
    }

    private function createLargeTestProject(int $fileCount): void
    {
        $files = [];

        for ($i = 1; $i <= $fileCount; $i++) {
            $type = $i % 5;
            $content = match ($type) {
                0 => $this->getHashAnnotationFile('simple'),
                1 => $this->getConstructorFile('basic'),
                2 => $this->getReadOnlyFile('entity'),
                3 => $this->getTypedPropertyFile('untyped'),
                default => $this->getRouterDecoratorFile(),
            };

            $files["File{$i}.php"] = $content;
        }

        $this->createFiles($files);
    }

    private function createFiles(array $files): void
    {
        foreach ($files as $filename => $content) {
            file_put_contents($this->testProjectDir . '/' . $filename, $content);
        }
    }

    private function createTestFile(string $filename, string $content): void
    {
        file_put_contents($this->testProjectDir . '/' . $filename, $content);
    }

    private function createCustomRuleConfig(string ...$rules): string
    {
        $configFile = $this->testProjectDir . '/rector-config.php';
        $rulesConfig = '';

        foreach ($rules as $rule) {
            $rulesConfig .= "    \$rectorConfig->rule(\\Pgs\\HashIdBundle\\Rector\\{$rule}::class);\n";
        }

        $config = <<<PHP
<?php
use Rector\Config\RectorConfig;

return static function (RectorConfig \$rectorConfig): void {
    \$rectorConfig->paths(['{$this->testProjectDir}']);
{$rulesConfig}
};
PHP;

        file_put_contents($configFile, $config);
        return $configFile;
    }

    private function getAccuracyTestCases(): array
    {
        return [
            'HashAnnotationToAttributeRule' => [
                [
                    'input' => '<?php use Pgs\HashIdBundle\Annotation\Hash; /** @Hash("id") */ public function show($id) {}',
                    'expected' => '<?php use Pgs\HashIdBundle\Attribute\Hash; #[Hash("id")] public function show($id) {}',
                ],
                [
                    'input' => '<?php /** @Hash({"id", "parentId"}) */ public function show($id, $parentId) {}',
                    'expected' => '<?php #[Hash(["id", "parentId"])] public function show($id, $parentId) {}',
                ],
            ],
            'ConstructorPropertyPromotionRule' => [
                [
                    'input' => '<?php class A { private $x; public function __construct($x) { $this->x = $x; } }',
                    'expected' => '<?php class A { public function __construct(private $x) {} }',
                ],
            ],
        ];
    }

    private function getHashAnnotationFile(string $type): string
    {
        return match ($type) {
            'simple' => <<<'PHP'
<?php
namespace App\Controller;
use Pgs\HashIdBundle\Annotation\Hash;

class SimpleHashController
{
    /**
     * @Hash("id")
     */
    public function show(int $id) {}
}
PHP,
            'multiple' => <<<'PHP'
<?php
namespace App\Controller;
use Pgs\HashIdBundle\Annotation\Hash;

class MultipleHashController
{
    /**
     * @Hash("id")
     * @Hash("parentId")
     */
    public function show(int $id, int $parentId) {}
}
PHP,
            'complex' => <<<'PHP'
<?php
namespace App\Controller;
use Pgs\HashIdBundle\Annotation\Hash;

class ComplexHashController
{
    /**
     * @Hash({"id", "categoryId", "userId"})
     */
    public function show(int $id, int $categoryId, int $userId) {}
}
PHP,
        };
    }

    private function getConstructorFile(string $type): string
    {
        return match ($type) {
            'basic' => <<<'PHP'
<?php
class BasicService
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
PHP,
            'complex' => <<<'PHP'
<?php
class ComplexService
{
    private string $name;
    private int $count;
    private ?array $data;

    public function __construct(string $name, int $count, ?array $data = null)
    {
        $this->name = $name;
        $this->count = $count;
        $this->data = $data;
    }
}
PHP,
            'mixed' => <<<'PHP'
<?php
class MixedService
{
    private string $name;
    public int $count;

    public function __construct(string $name, int $count, private array $config)
    {
        $this->name = $name;
        $this->count = $count;
    }
}
PHP,
        };
    }

    private function getReadOnlyFile(string $type): string
    {
        return match ($type) {
            'entity' => <<<'PHP'
<?php
class Entity
{
    private string $id;
    private string $name;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): string { return $this->id; }
}
PHP,
            'value_object' => <<<'PHP'
<?php
final class ValueObject
{
    private float $amount;
    private string $currency;

    public function __construct(float $amount, string $currency)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }
}
PHP,
            'dto' => <<<'PHP'
<?php
class DTO
{
    public string $field1;
    public ?int $field2;

    public function __construct(string $field1, ?int $field2)
    {
        $this->field1 = $field1;
        $this->field2 = $field2;
    }
}
PHP,
        };
    }

    private function getTypedPropertyFile(string $type): string
    {
        return match ($type) {
            'untyped' => <<<'PHP'
<?php
class UntypedClass
{
    private $property1;
    protected $property2;
    public $property3;
}
PHP,
            'partial' => <<<'PHP'
<?php
class PartiallyTyped
{
    private string $typed;
    protected $untyped;
    public ?int $nullable;
}
PHP,
            'docblock' => <<<'PHP'
<?php
class DocblockTyped
{
    /** @var string */
    private $name;

    /** @var int[] */
    protected $numbers;
}
PHP,
        };
    }

    private function getRouterDecoratorFile(): string
    {
        return <<<'PHP'
<?php
namespace Pgs\HashIdBundle\Service;

use Symfony\Component\Routing\RouterInterface;

class RouterDecorator implements RouterInterface
{
    private $router;
    private $processor;

    public function __construct(RouterInterface $router, $processor)
    {
        $this->router = $router;
        $this->processor = $processor;
    }

    public function generate($route, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $parameters = $this->processor->encode($parameters);
        return $this->router->generate($route, $parameters, $referenceType);
    }
}
PHP;
    }

    private function getHasherFactoryFile(): string
    {
        return <<<'PHP'
<?php
namespace Pgs\HashIdBundle\Service;

class HasherFactory
{
    private $salt;
    private $minLength;
    private $alphabet;

    public function __construct($salt, $minLength = 0, $alphabet = null)
    {
        $this->salt = $salt;
        $this->minLength = $minLength;
        $this->alphabet = $alphabet;
    }

    public function createHasher()
    {
        return new Hasher($this->salt, $this->minLength, $this->alphabet);
    }
}
PHP;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . "/" . $object)) {
                    $this->removeDirectory($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        rmdir($dir);
    }
}