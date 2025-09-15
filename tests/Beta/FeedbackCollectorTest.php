<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Beta;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class FeedbackCollectorTest extends TestCase
{
    private string $feedbackDir;

    protected function setUp(): void
    {
        $this->feedbackDir = sys_get_temp_dir() . '/hashid-beta-feedback';
        if (!is_dir($this->feedbackDir)) {
            mkdir($this->feedbackDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->feedbackDir)) {
            $this->removeDirectory($this->feedbackDir);
        }
    }

    public function testCollectMigrationMetrics(): void
    {
        $collector = new FeedbackCollector($this->feedbackDir);

        $metrics = [
            'project_name' => 'test-project',
            'php_version' => '8.3.0',
            'symfony_version' => '6.4.0',
            'rector_version' => '1.0.0',
            'start_time' => time(),
            'end_time' => time() + 300,
            'files_processed' => 25,
            'files_modified' => 18,
            'automation_percentage' => 72.0,
            'manual_interventions' => 3,
            'errors' => [],
            'warnings' => ['Deprecated annotation found in Controller'],
        ];

        $feedbackId = $collector->collectMetrics($metrics);

        $this->assertNotEmpty($feedbackId);
        $this->assertFileExists($this->feedbackDir . '/' . $feedbackId . '.yaml');

        $savedMetrics = Yaml::parseFile($this->feedbackDir . '/' . $feedbackId . '.yaml');
        $this->assertEquals($metrics['automation_percentage'], $savedMetrics['automation_percentage']);
        $this->assertEquals($metrics['files_modified'], $savedMetrics['files_modified']);
    }

    public function testValidateAutomationPercentage(): void
    {
        $collector = new FeedbackCollector($this->feedbackDir);

        $metrics = [
            'files_processed' => 100,
            'files_modified' => 75,
            'automation_percentage' => 75.0,
        ];

        $this->assertTrue($collector->validateAutomationTarget($metrics, 70.0));
        $this->assertFalse($collector->validateAutomationTarget($metrics, 80.0));
    }

    public function testCollectErrorFeedback(): void
    {
        $collector = new FeedbackCollector($this->feedbackDir);

        $errorData = [
            'error_type' => 'RectorException',
            'message' => 'Unable to process annotation',
            'file' => 'src/Controller/TestController.php',
            'line' => 42,
            'rector_rule' => 'AnnotationToAttributeRector',
            'context' => [
                'original_code' => '@Hash("id")',
                'expected_code' => '#[Hash("id")]',
            ],
        ];

        $errorId = $collector->collectError($errorData);

        $this->assertNotEmpty($errorId);
        $this->assertFileExists($this->feedbackDir . '/errors/' . $errorId . '.yaml');
    }

    public function testAggregateMetrics(): void
    {
        $collector = new FeedbackCollector($this->feedbackDir);

        $metricsSet = [
            ['automation_percentage' => 70.0, 'files_modified' => 10],
            ['automation_percentage' => 75.0, 'files_modified' => 15],
            ['automation_percentage' => 80.0, 'files_modified' => 20],
        ];

        foreach ($metricsSet as $metrics) {
            $collector->collectMetrics($metrics);
        }

        $aggregated = $collector->aggregateMetrics();

        $this->assertEquals(75.0, $aggregated['average_automation']);
        $this->assertEquals(70.0, $aggregated['min_automation']);
        $this->assertEquals(80.0, $aggregated['max_automation']);
        $this->assertEquals(45, $aggregated['total_files_modified']);
    }

    public function testGenerateFeedbackReport(): void
    {
        $collector = new FeedbackCollector($this->feedbackDir);

        $metricsSet = [
            [
                'project_name' => 'project1',
                'automation_percentage' => 72.0,
                'manual_interventions' => 5,
                'duration_seconds' => 300,
            ],
            [
                'project_name' => 'project2',
                'automation_percentage' => 78.0,
                'manual_interventions' => 2,
                'duration_seconds' => 180,
            ],
        ];

        foreach ($metricsSet as $metrics) {
            $collector->collectMetrics($metrics);
        }

        $report = $collector->generateReport();

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('automation_analysis', $report);
        $this->assertArrayHasKey('performance_metrics', $report);
        $this->assertArrayHasKey('recommendations', $report);

        $this->assertGreaterThanOrEqual(70.0, $report['summary']['average_automation']);
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

class FeedbackCollector
{
    private string $feedbackDir;

    public function __construct(string $feedbackDir)
    {
        $this->feedbackDir = $feedbackDir;
        if (!is_dir($feedbackDir)) {
            mkdir($feedbackDir, 0777, true);
        }
        if (!is_dir($feedbackDir . '/errors')) {
            mkdir($feedbackDir . '/errors', 0777, true);
        }
    }

    public function collectMetrics(array $metrics): string
    {
        $feedbackId = uniqid('feedback_', true);
        $metrics['feedback_id'] = $feedbackId;
        $metrics['collected_at'] = date('Y-m-d H:i:s');

        $filePath = $this->feedbackDir . '/' . $feedbackId . '.yaml';
        file_put_contents($filePath, Yaml::dump($metrics, 4));

        return $feedbackId;
    }

    public function collectError(array $errorData): string
    {
        $errorId = uniqid('error_', true);
        $errorData['error_id'] = $errorId;
        $errorData['reported_at'] = date('Y-m-d H:i:s');

        $filePath = $this->feedbackDir . '/errors/' . $errorId . '.yaml';
        file_put_contents($filePath, Yaml::dump($errorData, 4));

        return $errorId;
    }

    public function validateAutomationTarget(array $metrics, float $targetPercentage): bool
    {
        return ($metrics['automation_percentage'] ?? 0) >= $targetPercentage;
    }

    public function aggregateMetrics(): array
    {
        $files = glob($this->feedbackDir . '/feedback_*.yaml');
        if (empty($files)) {
            return [];
        }

        $automationPercentages = [];
        $totalFilesModified = 0;

        foreach ($files as $file) {
            $metrics = Yaml::parseFile($file);
            if (isset($metrics['automation_percentage'])) {
                $automationPercentages[] = $metrics['automation_percentage'];
            }
            if (isset($metrics['files_modified'])) {
                $totalFilesModified += $metrics['files_modified'];
            }
        }

        return [
            'average_automation' => !empty($automationPercentages) ?
                array_sum($automationPercentages) / count($automationPercentages) : 0,
            'min_automation' => !empty($automationPercentages) ? min($automationPercentages) : 0,
            'max_automation' => !empty($automationPercentages) ? max($automationPercentages) : 0,
            'total_files_modified' => $totalFilesModified,
            'total_projects' => count($files),
        ];
    }

    public function generateReport(): array
    {
        $aggregated = $this->aggregateMetrics();
        $errors = glob($this->feedbackDir . '/errors/error_*.yaml');

        $report = [
            'summary' => $aggregated,
            'automation_analysis' => [
                'target_met' => ($aggregated['average_automation'] ?? 0) >= 70.0,
                'confidence_level' => $this->calculateConfidenceLevel($aggregated),
            ],
            'performance_metrics' => [
                'total_errors' => count($errors),
                'error_rate' => $aggregated['total_projects'] > 0 ?
                    count($errors) / $aggregated['total_projects'] : 0,
            ],
            'recommendations' => $this->generateRecommendations($aggregated, count($errors)),
        ];

        return $report;
    }

    private function calculateConfidenceLevel(array $aggregated): string
    {
        if (empty($aggregated) || $aggregated['total_projects'] < 5) {
            return 'low';
        }

        if ($aggregated['total_projects'] >= 10 &&
            $aggregated['average_automation'] >= 70 &&
            ($aggregated['max_automation'] - $aggregated['min_automation']) < 20) {
            return 'high';
        }

        return 'medium';
    }

    private function generateRecommendations(array $aggregated, int $errorCount): array
    {
        $recommendations = [];

        if (($aggregated['average_automation'] ?? 0) < 70) {
            $recommendations[] = 'Automation target not met. Review and enhance Rector rules.';
        }

        if ($errorCount > ($aggregated['total_projects'] ?? 1) * 0.5) {
            $recommendations[] = 'High error rate detected. Investigate common failure patterns.';
        }

        if (($aggregated['max_automation'] ?? 0) - ($aggregated['min_automation'] ?? 0) > 30) {
            $recommendations[] = 'High variance in automation rates. Some project types may need specific rules.';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Beta testing metrics look healthy. Ready for wider release.';
        }

        return $recommendations;
    }
}