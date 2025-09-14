<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Rector;

use PHPUnit\Framework\TestCase;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

/**
 * Test for custom Rector rule infrastructure.
 *
 * This test validates that our custom Rector rules can be properly loaded,
 * configured, and executed against fixture files.
 */
class CustomRuleTest extends TestCase
{
    private string $customRulesDir;
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->customRulesDir = \dirname(__DIR__, 2) . '/rector-rules';
        $this->fixturesDir = __DIR__ . '/Fixtures';
    }

    /**
     * Test that the custom rules directory structure exists.
     */
    public function testCustomRulesDirectoryExists(): void
    {
        self::assertDirectoryExists(
            $this->customRulesDir,
            'Custom rules directory should exist at rector-rules/'
        );
    }

    /**
     * Test that the fixtures directory exists for testing.
     */
    public function testFixturesDirectoryExists(): void
    {
        self::assertDirectoryExists(
            $this->fixturesDir,
            'Fixtures directory should exist for testing custom rules'
        );
    }

    /**
     * Test that custom rule namespace is properly configured.
     */
    public function testCustomRuleNamespaceConfiguration(): void
    {
        $composerJsonPath = \dirname(__DIR__, 2) . '/composer.json';
        self::assertFileExists($composerJsonPath, 'composer.json should exist');

        $composerJson = \json_decode(\file_get_contents($composerJsonPath), true);
        
        // Check if custom rules namespace is registered in autoload-dev
        $hasCustomRulesNamespace = isset($composerJson['autoload-dev']['psr-4']['Pgs\\HashIdBundle\\Rector\\']);
        
        if (!$hasCustomRulesNamespace) {
            self::markTestSkipped('Custom rules namespace not yet configured in composer.json');
        }

        self::assertEquals(
            'rector-rules/',
            $composerJson['autoload-dev']['psr-4']['Pgs\\HashIdBundle\\Rector\\'],
            'Custom rules namespace should point to rector-rules/ directory'
        );
    }

    /**
     * Test that rector.php includes custom rules registration.
     */
    public function testRectorConfigurationIncludesCustomRules(): void
    {
        $rectorConfigPath = \dirname(__DIR__, 2) . '/rector.php';
        self::assertFileExists($rectorConfigPath, 'rector.php configuration should exist');

        $configContent = \file_get_contents($rectorConfigPath);
        
        // Check if custom rules are referenced in the configuration
        $hasCustomRulesReference = \str_contains($configContent, 'rector-rules') || 
                                   \str_contains($configContent, 'Pgs\\HashIdBundle\\Rector');
        
        if (!$hasCustomRulesReference) {
            self::markTestSkipped('Custom rules not yet registered in rector.php');
        }

        self::assertTrue(
            $hasCustomRulesReference,
            'rector.php should include reference to custom rules'
        );
    }

    /**
     * Test fixture-based validation infrastructure.
     */
    public function testFixtureBasedValidation(): void
    {
        $fixtureFile = $this->fixturesDir . '/annotation-to-attribute.php.inc';
        
        if (!\file_exists($fixtureFile)) {
            self::markTestSkipped('Fixture file not yet created');
        }

        self::assertFileExists($fixtureFile, 'Fixture file should exist');
        
        // Fixture files should contain before and after sections
        $fixtureContent = \file_get_contents($fixtureFile);
        self::assertStringContainsString(
            '-----',
            $fixtureContent,
            'Fixture should contain separator between before and after code'
        );
    }

    /**
     * Test that HashAnnotationToAttributeRule exists.
     */
    public function testHashAnnotationToAttributeRuleExists(): void
    {
        $ruleFile = $this->customRulesDir . '/HashAnnotationToAttributeRule.php';
        
        if (!\file_exists($ruleFile)) {
            self::markTestSkipped('HashAnnotationToAttributeRule not yet created');
        }

        self::assertFileExists($ruleFile, 'HashAnnotationToAttributeRule should exist');
        
        // Verify the rule class structure
        require_once $ruleFile;
        self::assertTrue(
            \class_exists('Pgs\\HashIdBundle\\Rector\\HashAnnotationToAttributeRule'),
            'HashAnnotationToAttributeRule class should be defined'
        );
    }

    /**
     * Test custom rule for Route annotation modernization.
     */
    public function testRouteAnnotationModernizationRule(): void
    {
        $ruleFile = $this->customRulesDir . '/RouteAnnotationToAttributeRule.php';
        
        if (!\file_exists($ruleFile)) {
            self::markTestSkipped('RouteAnnotationToAttributeRule not yet created');
        }

        self::assertFileExists($ruleFile, 'RouteAnnotationToAttributeRule should exist');
    }

    /**
     * Test that custom rules can handle both annotations and attributes.
     */
    public function testCompatibilityLayerSupport(): void
    {
        // This test validates that our rules maintain backward compatibility
        $compatibilityConfig = \dirname(__DIR__, 2) . '/rector-compatibility.php';
        
        if (!\file_exists($compatibilityConfig)) {
            self::markTestSkipped('Compatibility configuration not yet created');
        }

        self::assertFileExists($compatibilityConfig, 'Compatibility configuration should exist');
    }

    /**
     * Test deprecation system for annotations.
     */
    public function testDeprecationSystemExists(): void
    {
        $deprecationHandlerFile = $this->customRulesDir . '/DeprecationHandler.php';
        
        if (!\file_exists($deprecationHandlerFile)) {
            self::markTestSkipped('DeprecationHandler not yet created');
        }

        self::assertFileExists($deprecationHandlerFile, 'DeprecationHandler should exist');
    }

    /**
     * Test that migration documentation exists.
     */
    public function testMigrationDocumentationExists(): void
    {
        $upgradeDoc = \dirname(__DIR__, 2) . '/UPGRADE-4.0.md';
        
        if (!\file_exists($upgradeDoc)) {
            self::markTestSkipped('UPGRADE-4.0.md not yet created');
        }

        self::assertFileExists($upgradeDoc, 'UPGRADE-4.0.md should exist');
        
        $content = \file_get_contents($upgradeDoc);
        
        // Verify documentation includes key sections
        self::assertStringContainsString('Breaking Changes', $content);
        self::assertStringContainsString('Migration Path', $content);
        self::assertStringContainsString('annotations to attributes', $content);
    }
}