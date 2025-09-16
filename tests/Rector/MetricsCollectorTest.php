<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Rector;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Rector\MetricsCollector;

class MetricsCollectorTest extends TestCase
{
    private MetricsCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new MetricsCollector();
    }

    public function testInitialState(): void
    {
        $metrics = $this->collector->getMetrics();
        
        self::assertSame(0, $metrics['total_files']);
        self::assertSame(0, $metrics['files_modified']);
        self::assertSame(0, $metrics['total_changes']);
        self::assertSame(0, $metrics['automated_changes']);
        self::assertSame(0, $metrics['manual_changes']);
        self::assertSame(0.0, $metrics['automation_percentage']);
        self::assertIsArray($metrics['rules_applied']);
        self::assertEmpty($metrics['rules_applied']);
    }

    public function testTrackFileProcessing(): void
    {
        $this->collector->trackFileProcessed('src/Service/TestService.php');
        $this->collector->trackFileProcessed('src/Controller/TestController.php');
        
        $metrics = $this->collector->getMetrics();
        self::assertSame(2, $metrics['total_files']);
    }

    public function testTrackFileModification(): void
    {
        $this->collector->trackFileProcessed('src/Service/TestService.php');
        $this->collector->trackFileModified('src/Service/TestService.php', 5);
        
        $metrics = $this->collector->getMetrics();
        self::assertSame(1, $metrics['files_modified']);
        self::assertSame(5, $metrics['total_changes']);
        self::assertSame(5, $metrics['automated_changes']);
    }

    public function testTrackRuleApplication(): void
    {
        $ruleName = 'Rector\\Php81\\Rector\\Property\\ReadOnlyPropertyRector';
        
        $this->collector->trackRuleApplied($ruleName, 'src/Entity/User.php', true);
        $this->collector->trackRuleApplied($ruleName, 'src/Entity/Product.php', true);
        $this->collector->trackRuleApplied($ruleName, 'src/Entity/Order.php', false);
        
        $metrics = $this->collector->getMetrics();
        $ruleMetrics = $metrics['rules_applied'][$ruleName];
        
        self::assertSame(3, $ruleMetrics['total_applications']);
        self::assertSame(2, $ruleMetrics['successful']);
        self::assertSame(1, $ruleMetrics['failed']);
        self::assertEqualsWithDelta(66.67, $ruleMetrics['success_rate'], 0.01);
    }

    public function testCalculateAutomationPercentage(): void
    {
        $this->collector->trackFileProcessed('src/Service/TestService.php');
        $this->collector->trackFileModified('src/Service/TestService.php', 10);
        $this->collector->trackManualIntervention('src/Service/TestService.php', 2);
        
        $metrics = $this->collector->getMetrics();
        self::assertSame(10, $metrics['automated_changes']);
        self::assertSame(2, $metrics['manual_changes']);
        self::assertSame(12, $metrics['total_changes']);
        self::assertEqualsWithDelta(83.33, $metrics['automation_percentage'], 0.01);
    }

    public function testTrackTimeMetrics(): void
    {
        $startTime = \microtime(true);
        $this->collector->startTiming();
        
        // Simulate some processing time
        \usleep(100000); // 0.1 second
        
        $this->collector->stopTiming();
        $metrics = $this->collector->getMetrics();
        
        self::assertArrayHasKey('execution_time', $metrics);
        self::assertGreaterThan(0.09, $metrics['execution_time']);
        self::assertLessThan(0.2, $metrics['execution_time']);
    }

    public function testTrackErrorRate(): void
    {
        $this->collector->trackFileProcessed('src/Service/Service1.php');
        $this->collector->trackFileProcessed('src/Service/Service2.php');
        $this->collector->trackFileProcessed('src/Service/Service3.php');
        
        $this->collector->trackError('src/Service/Service2.php', 'Failed to parse file');
        
        $metrics = $this->collector->getMetrics();
        self::assertSame(1, $metrics['errors_count']);
        self::assertEqualsWithDelta(33.33, $metrics['error_rate'], 0.01);
        self::assertCount(1, $metrics['errors']);
        self::assertSame('Failed to parse file', $metrics['errors'][0]['message']);
    }

    public function testGetRuleEffectivenessReport(): void
    {
        $rule1 = 'Rector\\Php81\\Rector\\Property\\ReadOnlyPropertyRector';
        $rule2 = 'Rector\\Php81\\Rector\\ClassMethod\\NewInInitializerRector';
        
        $this->collector->trackRuleApplied($rule1, 'file1.php', true);
        $this->collector->trackRuleApplied($rule1, 'file2.php', true);
        $this->collector->trackRuleApplied($rule1, 'file3.php', false);
        
        $this->collector->trackRuleApplied($rule2, 'file1.php', true);
        $this->collector->trackRuleApplied($rule2, 'file2.php', false);
        
        $report = $this->collector->getRuleEffectivenessReport();
        
        self::assertCount(2, $report);
        self::assertArrayHasKey('rule_name', $report[0]);
        self::assertArrayHasKey('success_rate', $report[0]);
        self::assertArrayHasKey('total_applications', $report[0]);
    }

    public function testCalculateTimeSavings(): void
    {
        // Track automated changes (10 changes, estimated 5 minutes each manually)
        $this->collector->trackFileModified('src/Service/Service.php', 10);
        
        // Set execution time to 30 seconds
        $this->collector->setExecutionTime(30);
        
        // Estimate 5 minutes per manual change
        $estimatedManualTime = 10 * 5 * 60; // 3000 seconds
        $actualTime = 30; // seconds
        $timeSaved = $estimatedManualTime - $actualTime;
        
        $metrics = $this->collector->getMetrics();
        $this->collector->calculateTimeSavings(5 * 60); // 5 minutes per change
        
        $savingsMetrics = $this->collector->getTimeSavingsMetrics();
        self::assertEqualsWithDelta($timeSaved, $savingsMetrics['time_saved_seconds'], 0.1);
        self::assertEqualsWithDelta(99.0, $savingsMetrics['time_savings_percentage'], 0.1);
    }

    public function testResetMetrics(): void
    {
        $this->collector->trackFileProcessed('test.php');
        $this->collector->trackFileModified('test.php', 5);
        
        $metrics = $this->collector->getMetrics();
        self::assertSame(1, $metrics['total_files']);
        self::assertSame(5, $metrics['total_changes']);
        
        $this->collector->reset();
        
        $metrics = $this->collector->getMetrics();
        self::assertSame(0, $metrics['total_files']);
        self::assertSame(0, $metrics['total_changes']);
    }

    public function testExportMetricsAsJson(): void
    {
        $this->collector->trackFileProcessed('test.php');
        $this->collector->trackFileModified('test.php', 3);
        
        $json = $this->collector->exportAsJson();
        $decoded = \json_decode($json, true);
        
        self::assertIsArray($decoded);
        self::assertSame(1, $decoded['total_files']);
        self::assertSame(3, $decoded['total_changes']);
    }

    public function testGetSummaryStatistics(): void
    {
        $this->collector->trackFileProcessed('file1.php');
        $this->collector->trackFileProcessed('file2.php');
        $this->collector->trackFileModified('file1.php', 10);
        $this->collector->trackManualIntervention('file2.php', 2);
        
        $summary = $this->collector->getSummaryStatistics();
        
        self::assertArrayHasKey('total_files', $summary);
        self::assertArrayHasKey('files_modified', $summary);
        self::assertArrayHasKey('automation_rate', $summary);
        self::assertArrayHasKey('average_changes_per_file', $summary);
        self::assertSame(2, $summary['total_files']);
        self::assertSame(1, $summary['files_modified']);
        self::assertEqualsWithDelta(83.33, $summary['automation_rate'], 0.01);
        self::assertSame(10.0, $summary['average_changes_per_file']);
    }

    public function testMeetsAutomationTarget(): void
    {
        // Test below target
        $this->collector->trackFileModified('file.php', 60);
        $this->collector->trackManualIntervention('file.php', 40);
        
        self::assertFalse($this->collector->meetsAutomationTarget(70.0));
        
        // Reset and test above target
        $this->collector->reset();
        $this->collector->trackFileModified('file.php', 85);
        $this->collector->trackManualIntervention('file.php', 15);
        
        self::assertTrue($this->collector->meetsAutomationTarget(70.0));
    }
}