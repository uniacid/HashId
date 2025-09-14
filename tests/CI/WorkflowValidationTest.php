<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\CI;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests for CI/CD workflow configuration validation.
 * 
 * @coversNothing
 */
final class WorkflowValidationTest extends TestCase
{
    public function testMainCiWorkflowExists(): void
    {
        $workflowPath = $this->getProjectRoot() . '/.github/workflows/ci.yml';
        self::assertFileExists($workflowPath, 'Main CI workflow file should exist');
    }

    public function testMainCiWorkflowSyntax(): void
    {
        $workflowPath = $this->getProjectRoot() . '/.github/workflows/ci.yml';
        if (!file_exists($workflowPath)) {
            self::markTestSkipped('CI workflow file does not exist yet');
        }

        $content = file_get_contents($workflowPath);
        self::assertNotFalse($content, 'Should be able to read workflow file');

        try {
            $parsed = Yaml::parse($content);
            self::assertIsArray($parsed, 'Workflow should be valid YAML');
        } catch (\Exception $e) {
            self::fail('Workflow YAML syntax error: ' . $e->getMessage());
        }
    }

    public function testMainCiWorkflowHasRequiredJobs(): void
    {
        $workflow = $this->parseWorkflow('ci.yml');
        if (!$workflow) {
            self::markTestSkipped('CI workflow file does not exist yet');
        }

        self::assertArrayHasKey('jobs', $workflow, 'Workflow should have jobs');
        
        $expectedJobs = ['tests', 'quality-checks'];
        foreach ($expectedJobs as $job) {
            self::assertArrayHasKey($job, $workflow['jobs'], "Should have {$job} job");
        }
    }

    public function testMainCiWorkflowHasPhpMatrix(): void
    {
        $workflow = $this->parseWorkflow('ci.yml');
        if (!$workflow) {
            self::markTestSkipped('CI workflow file does not exist yet');
        }

        $testsJob = $workflow['jobs']['tests'] ?? null;
        self::assertNotNull($testsJob, 'Tests job should exist');
        
        $strategy = $testsJob['strategy'] ?? null;
        self::assertNotNull($strategy, 'Tests job should have strategy');
        
        $matrix = $strategy['matrix'] ?? null;
        self::assertNotNull($matrix, 'Tests job should have matrix strategy');
        
        $phpVersions = $matrix['php'] ?? null;
        self::assertNotNull($phpVersions, 'Matrix should include PHP versions');
        
        $expectedPhpVersions = ['8.1', '8.2', '8.3'];
        foreach ($expectedPhpVersions as $version) {
            self::assertContains($version, $phpVersions, "Should include PHP {$version}");
        }
    }

    public function testMainCiWorkflowHasSymfonyMatrix(): void
    {
        $workflow = $this->parseWorkflow('ci.yml');
        if (!$workflow) {
            self::markTestSkipped('CI workflow file does not exist yet');
        }

        $testsJob = $workflow['jobs']['tests'] ?? null;
        $matrix = $testsJob['strategy']['matrix'] ?? null;
        
        $symfonyVersions = $matrix['symfony'] ?? null;
        self::assertNotNull($symfonyVersions, 'Matrix should include Symfony versions');
        
        $expectedSymfonyVersions = ['6.4.*', '7.0.*'];
        foreach ($expectedSymfonyVersions as $version) {
            self::assertContains($version, $symfonyVersions, "Should include Symfony {$version}");
        }
    }

    public function testRectorWorkflowExists(): void
    {
        $workflowPath = $this->getProjectRoot() . '/.github/workflows/rector.yml';
        self::assertFileExists($workflowPath, 'Rector workflow file should exist');
    }

    public function testRectorWorkflowSyntax(): void
    {
        $workflowPath = $this->getProjectRoot() . '/.github/workflows/rector.yml';
        if (!file_exists($workflowPath)) {
            self::markTestSkipped('Rector workflow file does not exist yet');
        }

        $content = file_get_contents($workflowPath);
        self::assertNotFalse($content, 'Should be able to read rector workflow file');

        try {
            $parsed = Yaml::parse($content);
            self::assertIsArray($parsed, 'Rector workflow should be valid YAML');
        } catch (\Exception $e) {
            self::fail('Rector workflow YAML syntax error: ' . $e->getMessage());
        }
    }

    public function testQualityGateThresholds(): void
    {
        // Test PHPStan configuration
        $phpstanPath = $this->getProjectRoot() . '/phpstan.neon.dist';
        if (file_exists($phpstanPath)) {
            $content = file_get_contents($phpstanPath);
            self::assertStringContainsString('level: 9', $content, 'PHPStan should be set to level 9');
        }

        // Test PHPUnit coverage requirements
        $phpunitPath = $this->getProjectRoot() . '/phpunit.xml.dist';
        if (file_exists($phpunitPath)) {
            $content = file_get_contents($phpunitPath);
            self::assertStringContainsString('<coverage>', $content, 'PHPUnit should have coverage configuration');
        }
    }

    public function testWorkflowHasRequiredSteps(): void
    {
        $workflow = $this->parseWorkflow('ci.yml');
        if (!$workflow) {
            self::markTestSkipped('CI workflow file does not exist yet');
        }

        $testsJob = $workflow['jobs']['tests'] ?? null;
        $steps = $testsJob['steps'] ?? [];
        
        $stepNames = array_map(function ($step) {
            return $step['name'] ?? $step['uses'] ?? 'unnamed';
        }, $steps);

        $hasCheckout = false;
        foreach ($steps as $step) {
            if (($step['uses'] ?? '') === 'actions/checkout@v4') {
                $hasCheckout = true;
                break;
            }
        }
        self::assertTrue($hasCheckout, 'Should checkout code');
        
        $hasPhpSetup = false;
        foreach ($steps as $step) {
            if (str_contains($step['uses'] ?? '', 'shivammathur/setup-php') || str_contains($step['name'] ?? '', 'Setup PHP')) {
                $hasPhpSetup = true;
                break;
            }
        }
        self::assertTrue($hasPhpSetup, 'Should setup PHP');
        
        $hasComposerInstall = false;
        foreach ($stepNames as $step) {
            if (str_contains($step, 'composer')) {
                $hasComposerInstall = true;
                break;
            }
        }
        self::assertTrue($hasComposerInstall, 'Should install Composer dependencies');
    }

    private function parseWorkflow(string $filename): ?array
    {
        $workflowPath = $this->getProjectRoot() . '/.github/workflows/' . $filename;
        if (!file_exists($workflowPath)) {
            return null;
        }

        $content = file_get_contents($workflowPath);
        if (!$content) {
            return null;
        }

        try {
            return Yaml::parse($content);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getProjectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}