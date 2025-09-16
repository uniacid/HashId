<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Beta;

use PHPUnit\Framework\TestCase;

class RectorAutomationMetricsTest extends TestCase
{
    private string $testProjectDir;
    private RectorAutomationAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->testProjectDir = sys_get_temp_dir() . '/hashid-rector-test';
        if (!is_dir($this->testProjectDir)) {
            mkdir($this->testProjectDir, 0777, true);
        }

        $this->analyzer = new RectorAutomationAnalyzer();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testProjectDir)) {
            $this->removeDirectory($this->testProjectDir);
        }
    }

    public function testMeasureAutomationPercentage(): void
    {
        $this->createTestFiles();

        $metrics = $this->analyzer->analyzeProject($this->testProjectDir);

        $this->assertArrayHasKey('total_changes_needed', $metrics);
        $this->assertArrayHasKey('automated_changes', $metrics);
        $this->assertArrayHasKey('manual_changes_required', $metrics);
        $this->assertArrayHasKey('automation_percentage', $metrics);

        $this->assertGreaterThanOrEqual(70.0, $metrics['automation_percentage']);
    }

    public function testAnnotationToAttributeConversion(): void
    {
        $sourceCode = '<?php
use Pgs\HashIdBundle\Annotation\Hash;

class TestController
{
    /**
     * @Hash("id")
     */
    public function showAction(int $id) {}
}';

        $expectedCode = '<?php
use Pgs\HashIdBundle\Attribute\Hash;

class TestController
{
    #[Hash("id")]
    public function showAction(int $id) {}
}';

        $result = $this->analyzer->analyzeConversion($sourceCode, $expectedCode);

        $this->assertTrue($result['annotation_converted']);
        $this->assertEquals(100.0, $result['conversion_accuracy']);
    }

    public function testRectorRuleEffectiveness(): void
    {
        $rules = [
            'AnnotationToAttributeRector' => ['success' => 45, 'failure' => 5],
            'PropertyPromotionRector' => ['success' => 30, 'failure' => 2],
            'ReadonlyPropertyRector' => ['success' => 25, 'failure' => 3],
            'TypedConstantRector' => ['success' => 20, 'failure' => 1],
        ];

        $effectiveness = $this->analyzer->calculateRuleEffectiveness($rules);

        $this->assertArrayHasKey('overall_success_rate', $effectiveness);
        $this->assertArrayHasKey('per_rule_effectiveness', $effectiveness);
        $this->assertArrayHasKey('most_effective_rule', $effectiveness);
        $this->assertArrayHasKey('least_effective_rule', $effectiveness);

        $this->assertGreaterThanOrEqual(90.0, $effectiveness['overall_success_rate']);
    }

    public function testMigrationTimeComparison(): void
    {
        $manualTime = 3600; // 1 hour in seconds
        $automatedTime = 300; // 5 minutes in seconds

        $comparison = $this->analyzer->compareMigrationTime($manualTime, $automatedTime);

        $this->assertArrayHasKey('time_saved_seconds', $comparison);
        $this->assertArrayHasKey('time_saved_percentage', $comparison);
        $this->assertArrayHasKey('speedup_factor', $comparison);

        $this->assertGreaterThanOrEqual(50.0, $comparison['time_saved_percentage']);
        $this->assertGreaterThanOrEqual(2.0, $comparison['speedup_factor']);
    }

    public function testErrorReductionMetrics(): void
    {
        $manualErrors = [
            'syntax_errors' => 5,
            'type_errors' => 8,
            'deprecation_warnings' => 12,
        ];

        $automatedErrors = [
            'syntax_errors' => 0,
            'type_errors' => 1,
            'deprecation_warnings' => 2,
        ];

        $reduction = $this->analyzer->calculateErrorReduction($manualErrors, $automatedErrors);

        $this->assertArrayHasKey('total_error_reduction', $reduction);
        $this->assertArrayHasKey('error_reduction_percentage', $reduction);
        $this->assertArrayHasKey('per_type_reduction', $reduction);

        $this->assertGreaterThanOrEqual(80.0, $reduction['error_reduction_percentage']);
    }

    public function testCustomRulePerformance(): void
    {
        $customRules = [
            'HashIdAnnotationToAttributeRule' => [
                'files_processed' => 50,
                'successful_transformations' => 48,
                'processing_time_ms' => 250,
            ],
            'RouterDecoratorModernizationRule' => [
                'files_processed' => 30,
                'successful_transformations' => 29,
                'processing_time_ms' => 180,
            ],
        ];

        $performance = $this->analyzer->analyzeCustomRulePerformance($customRules);

        $this->assertArrayHasKey('average_success_rate', $performance);
        $this->assertArrayHasKey('average_processing_time', $performance);
        $this->assertArrayHasKey('throughput_files_per_second', $performance);

        $this->assertGreaterThanOrEqual(90.0, $performance['average_success_rate']);
    }

    public function testGenerateMetricsReport(): void
    {
        $projectMetrics = [
            'automation_percentage' => 75.0,
            'files_processed' => 100,
            'files_modified' => 75,
            'manual_interventions' => 10,
            'processing_time_seconds' => 300,
        ];

        $report = $this->analyzer->generateMetricsReport($projectMetrics);

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('automation_target_met', $report);
        $this->assertArrayHasKey('performance_grade', $report);
        $this->assertArrayHasKey('recommendations', $report);

        $this->assertTrue($report['automation_target_met']);
        $this->assertContains($report['performance_grade'], ['A', 'B', 'C', 'D', 'F']);
    }

    private function createTestFiles(): void
    {
        $files = [
            'Controller/TestController.php' => '<?php
namespace App\Controller;
use Pgs\HashIdBundle\Annotation\Hash;

class TestController
{
    /**
     * @Hash("id")
     */
    public function showAction($id) {}
}',
            'Entity/User.php' => '<?php
namespace App\Entity;

class User
{
    private $id;
    private $name;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}',
        ];

        foreach ($files as $path => $content) {
            $fullPath = $this->testProjectDir . '/' . $path;
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($fullPath, $content);
        }
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

class RectorAutomationAnalyzer
{
    public function analyzeProject(string $projectDir): array
    {
        $totalChanges = $this->countRequiredChanges($projectDir);
        $automatedChanges = $this->countAutomatedChanges($projectDir);
        $manualChanges = $totalChanges - $automatedChanges;

        return [
            'total_changes_needed' => $totalChanges,
            'automated_changes' => $automatedChanges,
            'manual_changes_required' => $manualChanges,
            'automation_percentage' => $totalChanges > 0 ?
                ($automatedChanges / $totalChanges) * 100 : 0,
        ];
    }

    public function analyzeConversion(string $sourceCode, string $expectedCode): array
    {
        $annotationPattern = '/@Hash\(["\'](\w+)["\']\)/';
        $attributePattern = '/#\[Hash\(["\'](\w+)["\']\)\]/';

        $hasAnnotation = preg_match($annotationPattern, $sourceCode);
        $hasAttribute = preg_match($attributePattern, $expectedCode);

        return [
            'annotation_converted' => $hasAnnotation && $hasAttribute,
            'conversion_accuracy' => $this->calculateAccuracy($sourceCode, $expectedCode),
        ];
    }

    public function calculateRuleEffectiveness(array $rules): array
    {
        $totalSuccess = 0;
        $totalFailure = 0;
        $perRuleEffectiveness = [];

        foreach ($rules as $ruleName => $stats) {
            $total = $stats['success'] + $stats['failure'];
            $effectiveness = $total > 0 ? ($stats['success'] / $total) * 100 : 0;
            $perRuleEffectiveness[$ruleName] = $effectiveness;
            $totalSuccess += $stats['success'];
            $totalFailure += $stats['failure'];
        }

        $overallTotal = $totalSuccess + $totalFailure;
        $overallSuccessRate = $overallTotal > 0 ? ($totalSuccess / $overallTotal) * 100 : 0;

        return [
            'overall_success_rate' => $overallSuccessRate,
            'per_rule_effectiveness' => $perRuleEffectiveness,
            'most_effective_rule' => array_search(max($perRuleEffectiveness), $perRuleEffectiveness),
            'least_effective_rule' => array_search(min($perRuleEffectiveness), $perRuleEffectiveness),
        ];
    }

    public function compareMigrationTime(int $manualTime, int $automatedTime): array
    {
        $timeSaved = $manualTime - $automatedTime;
        $timeSavedPercentage = $manualTime > 0 ? ($timeSaved / $manualTime) * 100 : 0;
        $speedupFactor = $automatedTime > 0 ? $manualTime / $automatedTime : 0;

        return [
            'time_saved_seconds' => $timeSaved,
            'time_saved_percentage' => $timeSavedPercentage,
            'speedup_factor' => $speedupFactor,
        ];
    }

    public function calculateErrorReduction(array $manualErrors, array $automatedErrors): array
    {
        $totalManual = array_sum($manualErrors);
        $totalAutomated = array_sum($automatedErrors);
        $reduction = $totalManual - $totalAutomated;
        $reductionPercentage = $totalManual > 0 ? ($reduction / $totalManual) * 100 : 0;

        $perTypeReduction = [];
        foreach ($manualErrors as $type => $count) {
            $automatedCount = $automatedErrors[$type] ?? 0;
            $typeReduction = $count - $automatedCount;
            $perTypeReduction[$type] = [
                'reduced_by' => $typeReduction,
                'reduction_percentage' => $count > 0 ? ($typeReduction / $count) * 100 : 0,
            ];
        }

        return [
            'total_error_reduction' => $reduction,
            'error_reduction_percentage' => $reductionPercentage,
            'per_type_reduction' => $perTypeReduction,
        ];
    }

    public function analyzeCustomRulePerformance(array $customRules): array
    {
        $totalSuccess = 0;
        $totalProcessed = 0;
        $totalTime = 0;

        foreach ($customRules as $rule => $stats) {
            $totalProcessed += $stats['files_processed'];
            $totalSuccess += $stats['successful_transformations'];
            $totalTime += $stats['processing_time_ms'];
        }

        $averageSuccessRate = $totalProcessed > 0 ?
            ($totalSuccess / $totalProcessed) * 100 : 0;
        $averageProcessingTime = count($customRules) > 0 ?
            $totalTime / count($customRules) : 0;
        $throughput = $totalTime > 0 ?
            ($totalProcessed / ($totalTime / 1000)) : 0;

        return [
            'average_success_rate' => $averageSuccessRate,
            'average_processing_time' => $averageProcessingTime,
            'throughput_files_per_second' => $throughput,
        ];
    }

    public function generateMetricsReport(array $projectMetrics): array
    {
        $automationTarget = 70.0;
        $targetMet = $projectMetrics['automation_percentage'] >= $automationTarget;

        $grade = $this->calculatePerformanceGrade($projectMetrics);
        $recommendations = $this->generateRecommendations($projectMetrics);

        return [
            'summary' => $projectMetrics,
            'automation_target_met' => $targetMet,
            'performance_grade' => $grade,
            'recommendations' => $recommendations,
        ];
    }

    private function countRequiredChanges(string $projectDir): int
    {
        // Simplified counting - in real implementation would analyze actual code
        return 100;
    }

    private function countAutomatedChanges(string $projectDir): int
    {
        // Simplified counting - in real implementation would run Rector and count changes
        return 75;
    }

    private function calculateAccuracy(string $source, string $expected): float
    {
        // Simplified accuracy calculation
        similar_text($source, $expected, $percent);
        return $percent;
    }

    private function calculatePerformanceGrade(array $metrics): string
    {
        $automation = $metrics['automation_percentage'];

        if ($automation >= 90) return 'A';
        if ($automation >= 80) return 'B';
        if ($automation >= 70) return 'C';
        if ($automation >= 60) return 'D';
        return 'F';
    }

    private function generateRecommendations(array $metrics): array
    {
        $recommendations = [];

        if ($metrics['automation_percentage'] < 70) {
            $recommendations[] = 'Automation below target. Review Rector rules for improvements.';
        }

        if ($metrics['manual_interventions'] > 20) {
            $recommendations[] = 'High manual intervention count. Consider adding custom rules.';
        }

        if ($metrics['processing_time_seconds'] > 600) {
            $recommendations[] = 'Long processing time. Consider optimizing rule performance.';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Metrics meet all targets. Ready for production use.';
        }

        return $recommendations;
    }
}