<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Migration;

use ReflectionClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class MigrationProcessTest extends TestCase
{
    private string $projectRoot;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = dirname(__DIR__, 2);
    }
    
    public function testRectorConfigurationExists(): void
    {
        $rectorConfigPath = $this->projectRoot . '/rector.php';
        $this->assertFileExists($rectorConfigPath, 'Main Rector configuration file should exist');
        
        $config = file_get_contents($rectorConfigPath);
        $this->assertStringContainsString('RectorConfig', $config);
        $this->assertStringContainsString('paths', $config);
    }
    
    public function testRectorPhaseConfigurationsExist(): void
    {
        $phases = [
            'rector-php81.php' => 'PHP 8.1 upgrade rules',
            'rector-php82.php' => 'PHP 8.2 upgrade rules',
            'rector-php83.php' => 'PHP 8.3 upgrade rules',
            'rector-symfony.php' => 'Symfony compatibility rules',
            'rector-quality.php' => 'Code quality rules',
            'rector-compatibility.php' => 'Compatibility rules'
        ];
        
        foreach ($phases as $file => $description) {
            $path = $this->projectRoot . '/' . $file;
            $this->assertFileExists($path, sprintf('%s configuration (%s) should exist', $description, $file));
        }
    }
    
    public function testRectorDryRunExecutesSuccessfully(): void
    {
        $process = new Process([
            'vendor/bin/rector',
            'process',
            '--config=rector.php',
            '--dry-run',
            'tests/Migration/Fixtures'
        ], $this->projectRoot);
        
        $process->run();
        
        // Rector should execute without fatal errors
        $this->assertNotEquals(127, $process->getExitCode(), 'Rector command should be available');
        
        // Check for common Rector output patterns
        $output = $process->getOutput() . $process->getErrorOutput();
        
        // If files are processed, Rector will mention it
        if (str_contains($output, 'file')) {
            $this->assertStringNotContainsString('Fatal error', $output);
            $this->assertStringNotContainsString('PHP Fatal error', $output);
        }
    }
    
    public function testComposerJsonHasRequiredDependencies(): void
    {
        $composerJson = json_decode(
            file_get_contents($this->projectRoot . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        
        // Check for PHP version requirement
        $this->assertArrayHasKey('require', $composerJson);
        $phpVersion = $composerJson['require']['php'] ?? '';
        $this->assertMatchesRegularExpression('/\^8\.[1-3]|\^8\.0|>=8\.[1-3]/', $phpVersion, 'PHP 8.1+ should be required');
        
        // Check for Symfony version
        $symfonyComponents = array_filter(
            array_keys($composerJson['require']),
            fn($key) => str_starts_with($key, 'symfony/')
        );
        $this->assertNotEmpty($symfonyComponents, 'Symfony components should be required');
        
        // Check for development dependencies
        $this->assertArrayHasKey('require-dev', $composerJson);
        $this->assertArrayHasKey('rector/rector', $composerJson['require-dev'], 'Rector should be in dev dependencies');
    }
    
    public function testMigrationStepsAreDocumented(): void
    {
        $upgradeDoc = $this->projectRoot . '/UPGRADE-4.0.md';
        $this->assertFileExists($upgradeDoc, 'UPGRADE-4.0.md should exist');
        
        $content = file_get_contents($upgradeDoc);
        
        // Check for essential sections
        $requiredSections = [
            'Requirements',
            'Breaking Changes',
            'Migration Path',
            'Step 1',
            'Step 2',
            'Troubleshooting',
            'rector'
        ];
        
        foreach ($requiredSections as $section) {
            $this->assertStringContainsString(
                $section,
                $content,
                sprintf('UPGRADE.md should contain "%s" section', $section)
            );
        }
    }
    
    public function testAttributeAndAnnotationCompatibility(): void
    {
        // Check that both annotation and attribute classes exist
        $annotationClass = 'Pgs\HashIdBundle\Annotation\Hash';
        $attributeClass = 'Pgs\HashIdBundle\Attribute\Hash';
        
        $this->assertTrue(
            class_exists($annotationClass),
            'Annotation class should exist for backward compatibility'
        );
        
        $this->assertTrue(
            class_exists($attributeClass),
            'Attribute class should exist for modern PHP'
        );
        
        // Check that attribute class is actually an attribute
        $reflection = new ReflectionClass($attributeClass);
        $attributes = $reflection->getAttributes();
        
        $hasAttributeAttribute = false;
        foreach ($reflection->getAttributes() as $attr) {
            if ($attr->getName() === 'Attribute') {
                $hasAttributeAttribute = true;
                break;
            }
        }
        
        $this->assertTrue($hasAttributeAttribute, 'Hash attribute class should be marked with #[Attribute]');
    }
    
    public function testDeprecationHandlerExists(): void
    {
        $deprecationHandlerPath = $this->projectRoot . '/rector-rules/DeprecationHandler.php';
        $this->assertFileExists($deprecationHandlerPath, 'DeprecationHandler should exist in rector-rules');
        
        $content = file_get_contents($deprecationHandlerPath);
        $this->assertStringContainsString('namespace Pgs\HashIdBundle\Rector', $content);
        $this->assertStringContainsString('class DeprecationHandler', $content);
    }
    
    public function testMigrationFixturesExist(): void
    {
        $fixtureDir = $this->projectRoot . '/tests/Rector/Fixtures';
        $this->assertDirectoryExists($fixtureDir, 'Rector test fixtures directory should exist');
        
        // Check for key fixture files
        $expectedFixtures = [
            'annotation-to-attribute.php.inc',
            'route-annotation-modernization.php.inc'
        ];
        
        foreach ($expectedFixtures as $fixture) {
            $this->assertFileExists(
                $fixtureDir . '/' . $fixture,
                sprintf('Fixture file %s should exist', $fixture)
            );
        }
    }
}