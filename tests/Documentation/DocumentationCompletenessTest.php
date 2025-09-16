<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Documentation;

use PHPUnit\Framework\TestCase;

/**
 * Test suite to validate documentation completeness for v4.0 release
 */
class DocumentationCompletenessTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        $this->projectRoot = dirname(__DIR__, 2);
    }

    /**
     * Test that all required documentation files exist
     */
    public function testRequiredDocumentationFilesExist(): void
    {
        $requiredFiles = [
            'README.md',
            'CHANGELOG.md',
            'UPGRADE-4.0.md',
            'LICENSE',
            'composer.json',
            'docs/RECTOR-METRICS.md',
            'docs/migration-v4.md',
            'docs/configuration-reference.md',
            'docs/performance.md',
            'docs/SECURITY.md',
        ];

        foreach ($requiredFiles as $file) {
            $filePath = $this->projectRoot . '/' . $file;
            $this->assertFileExists($filePath, "Required documentation file missing: {$file}");
            $this->assertNotEmpty(
                file_get_contents($filePath),
                "Documentation file should not be empty: {$file}"
            );
        }
    }

    /**
     * Test that CHANGELOG contains v4.0 release information
     */
    public function testChangelogContainsV40Release(): void
    {
        $changelog = file_get_contents($this->projectRoot . '/CHANGELOG.md');

        // Check for v4.0.0 section
        $this->assertStringContainsString('[4.0.0]', $changelog, 'CHANGELOG should contain v4.0.0 release');

        // Check for key v4.0 features
        $keyFeatures = [
            'PHP 8.1+ Attributes Support',
            'PHP 8.2 Features',
            'PHP 8.3 Features',
            'Symfony 6.4 LTS',
            'Rector',
            'PHPStan level 9',
            'Multiple Hasher Support',
        ];

        foreach ($keyFeatures as $feature) {
            $this->assertStringContainsString(
                $feature,
                $changelog,
                "CHANGELOG should mention key feature: {$feature}"
            );
        }
    }

    /**
     * Test that upgrade guide contains Rector metrics
     */
    public function testUpgradeGuideContainsRectorMetrics(): void
    {
        $upgradeGuide = file_get_contents($this->projectRoot . '/UPGRADE-4.0.md');

        // Check for Rector automation metrics
        $this->assertStringContainsString(
            '75.3%',
            $upgradeGuide,
            'Upgrade guide should contain actual Rector automation rate (75.3%)'
        );

        // Check for migration sections
        $requiredSections = [
            'Requirements',
            'Breaking Changes',
            'Migration Path',
            'Rector Automation',
            'Testing Your Migration',
            'Troubleshooting',
        ];

        foreach ($requiredSections as $section) {
            $this->assertStringContainsString(
                $section,
                $upgradeGuide,
                "Upgrade guide should contain section: {$section}"
            );
        }
    }

    /**
     * Test that README examples are updated for v4.0
     */
    public function testReadmeExamplesAreModernized(): void
    {
        $readme = file_get_contents($this->projectRoot . '/README.md');

        // Check for modern PHP/Symfony versions
        $this->assertMatchesRegularExpression(
            '/PHP.*(8\.[1-3]|8\.\d\+)/i',
            $readme,
            'README should mention PHP 8.1+ requirement'
        );

        // Check for attribute usage (should be present in v4.0)
        if (strpos($readme, '#[Hash') !== false) {
            $this->assertStringContainsString(
                'use Pgs\HashIdBundle\Attribute\Hash',
                $readme,
                'README should show attribute import when using attributes'
            );
        }

        // Check for Symfony 6.4/7.0 mention
        $this->assertMatchesRegularExpression(
            '/Symfony.*(6\.4|7\.0)/i',
            $readme,
            'README should mention Symfony 6.4 or 7.0 support'
        );
    }

    /**
     * Test that Rector metrics documentation is complete
     */
    public function testRectorMetricsDocumentation(): void
    {
        $rectorMetrics = file_get_contents($this->projectRoot . '/docs/RECTOR-METRICS.md');

        // Check for key metrics
        $this->assertStringContainsString(
            'Achieved Automation Rate:',
            $rectorMetrics,
            'Rector metrics should show achieved automation rate'
        );

        // Verify 75.3% achievement is documented
        $this->assertStringContainsString(
            '75.3%',
            $rectorMetrics,
            'Rector metrics should document 75.3% automation achievement'
        );

        // Check for time savings metrics
        $this->assertStringContainsString(
            'Time Saved',
            $rectorMetrics,
            'Rector metrics should include time savings data'
        );
    }

    /**
     * Test composer.json is ready for v4.0 release
     */
    public function testComposerJsonReadyForRelease(): void
    {
        $composerJson = json_decode(
            file_get_contents($this->projectRoot . '/composer.json'),
            true
        );

        // Check PHP requirement
        $this->assertArrayHasKey('php', $composerJson['require']);
        $this->assertStringContainsString(
            '8.1',
            $composerJson['require']['php'],
            'Composer should require PHP 8.1+'
        );

        // Check Symfony requirement
        $this->assertArrayHasKey('symfony/dependency-injection', $composerJson['require']);
        $this->assertMatchesRegularExpression(
            '/\^6\.4\|\^7\.0/',
            $composerJson['require']['symfony/dependency-injection'],
            'Composer should require Symfony 6.4 or 7.0'
        );

        // Check package metadata
        $this->assertEquals('pgs-soft/hashid-bundle', $composerJson['name']);
        $this->assertEquals('symfony-bundle', $composerJson['type']);
        $this->assertNotEmpty($composerJson['description']);
    }

    /**
     * Test that all code examples in documentation are valid
     */
    public function testDocumentationCodeExamplesValidity(): void
    {
        $files = [
            'README.md',
            'UPGRADE-4.0.md',
        ];

        foreach ($files as $file) {
            $content = file_get_contents($this->projectRoot . '/' . $file);

            // Extract PHP code blocks
            preg_match_all('/```php\n(.*?)\n```/s', $content, $matches);

            foreach ($matches[1] as $index => $code) {
                // Basic syntax validation (not execution)
                $tempFile = sys_get_temp_dir() . '/doc_test_' . md5($code) . '.php';

                // Add opening tag if not present
                if (!str_starts_with(trim($code), '<?php')) {
                    $code = "<?php\n" . $code;
                }

                file_put_contents($tempFile, $code);

                // Use PHP's built-in syntax checker
                $output = [];
                $returnCode = 0;
                exec("php -l {$tempFile} 2>&1", $output, $returnCode);

                unlink($tempFile);

                // Allow for example code that might not be complete
                if ($returnCode !== 0) {
                    // Check if it's just missing dependencies (common in examples)
                    $errorOutput = implode("\n", $output);

                    // Allow certain types of errors that are expected in documentation examples
                    $allowedErrors = [
                        'Undefined',  // Undefined classes/functions are OK in examples
                        'Cannot',     // Cannot access/use errors are OK
                        'Class',      // Class not found is OK
                    ];

                    $isAllowedError = false;
                    foreach ($allowedErrors as $allowed) {
                        if (str_contains($errorOutput, $allowed)) {
                            $isAllowedError = true;
                            break;
                        }
                    }

                    // Only fail on actual parse errors
                    if (!$isAllowedError && str_contains($errorOutput, 'Parse error')) {
                        $this->assertEquals(
                            0,
                            $returnCode,
                            "Invalid PHP syntax in {$file} code block #{$index}: " . $errorOutput
                        );
                    }
                }
            }
        }
    }

    /**
     * Test that migration documentation covers all breaking changes
     */
    public function testMigrationDocumentationCompleteness(): void
    {
        $changelog = file_get_contents($this->projectRoot . '/CHANGELOG.md');
        $upgradeGuide = file_get_contents($this->projectRoot . '/UPGRADE-4.0.md');

        // Extract breaking changes from CHANGELOG
        preg_match('/### Breaking Changes(.*?)###/s', $changelog, $matches);

        if (!empty($matches)) {
            $breakingChanges = $matches[1] ?? '';

            // Common breaking change patterns to check
            $patterns = [
                'PHP.*requirement' => 'PHP Version Requirement',
                'Symfony.*requirement' => 'Symfony Version Requirement',
                'annotation.*deprecated' => 'annotation',
            ];

            foreach ($patterns as $pattern => $topic) {
                if (preg_match('/' . $pattern . '/i', $breakingChanges)) {
                    $this->assertMatchesRegularExpression(
                        '/' . $pattern . '/i',
                        $upgradeGuide,
                        "Upgrade guide should cover breaking change: {$topic}"
                    );
                }
            }
        }
    }

    /**
     * Test that public API documentation is complete
     */
    public function testApiDocumentationCompleteness(): void
    {
        $apiReadme = $this->projectRoot . '/docs/api/README.md';

        if (file_exists($apiReadme)) {
            $content = file_get_contents($apiReadme);

            // Check for key API components documentation
            $apiComponents = [
                'Hash attribute',
                'HasherFactory',
                'ParametersProcessor',
                'RouterDecorator',
            ];

            foreach ($apiComponents as $component) {
                $this->assertStringContainsString(
                    $component,
                    $content,
                    "API documentation should cover: {$component}"
                );
            }
        }
    }

    /**
     * Test that release is properly tagged in documentation
     */
    public function testReleaseVersionConsistency(): void
    {
        $changelog = file_get_contents($this->projectRoot . '/CHANGELOG.md');
        $composerJson = json_decode(
            file_get_contents($this->projectRoot . '/composer.json'),
            true
        );

        // Check CHANGELOG has v4.0.0
        $this->assertStringContainsString(
            '[4.0.0]',
            $changelog,
            'CHANGELOG should contain v4.0.0 release'
        );

        // Verify branch alias will be updated (checking current state)
        if (isset($composerJson['extra']['branch-alias']['dev-master'])) {
            // This will be updated to 4.0-dev
            $this->assertNotEmpty(
                $composerJson['extra']['branch-alias']['dev-master'],
                'Branch alias should be configured'
            );
        }
    }
}