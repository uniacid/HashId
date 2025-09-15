<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Rector;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

class CustomRuleValidationTest extends TestCase
{
    private string $fixturesDir;
    private string $rectorBinary;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/Fixtures';
        $this->rectorBinary = __DIR__ . '/../../vendor/bin/rector';
    }

    /**
     * Test that all fixture files have valid before/after transformations.
     *
     * @dataProvider provideFixtureFiles
     */
    public function testFixtureTransformations(string $fixturePath): void
    {
        self::assertFileExists($fixturePath, 'Fixture file should exist');
        
        $content = \file_get_contents($fixturePath);
        self::assertNotEmpty($content, 'Fixture file should not be empty');
        
        // Check for separator between before and after code
        self::assertStringContainsString('-----', $content, 'Fixture should contain separator between before and after code');
        
        $parts = \explode('-----', $content, 2);
        $before = $parts[0];
        $after = $parts[1];
        
        self::assertNotEmpty(\trim($before), 'Before code should not be empty');
        self::assertNotEmpty(\trim($after), 'After code should not be empty');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function provideFixtureFiles(): array
    {
        $fixturesDir = __DIR__ . '/Fixtures';
        
        if (!\is_dir($fixturesDir)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()
            ->in($fixturesDir)
            ->name('*.php.inc');

        $fixtures = [];
        foreach ($finder as $file) {
            $fixtures[$file->getRelativePathname()] = [$file->getPathname()];
        }

        return $fixtures;
    }

    /**
     * Test that annotation to attribute transformation works correctly.
     */
    public function testAnnotationToAttributeTransformation(): void
    {
        $fixturePath = $this->fixturesDir . '/AnnotationToAttribute/annotation_to_attribute.php.inc';
        
        if (!\file_exists($fixturePath)) {
            self::markTestSkipped('Annotation to attribute fixture not found');
        }
        
        $content = \file_get_contents($fixturePath);
        $parts = \explode('-----', $content, 2);
        $before = $parts[0];
        $after = $parts[1];
        
        // Check that before code uses annotations
        self::assertStringContainsString('@Hash', $before, 'Before code should contain @Hash annotation');
        self::assertStringNotContainsString('#[Hash', $before, 'Before code should not contain #[Hash attribute');
        
        // Check that after code uses attributes
        self::assertStringNotContainsString('@Hash', $after, 'After code should not contain @Hash annotation');
        self::assertStringContainsString('#[Hash', $after, 'After code should contain #[Hash attribute');
    }

    /**
     * Test that constructor property promotion transformation works correctly.
     */
    public function testConstructorPropertyPromotionTransformation(): void
    {
        $fixturePath = $this->fixturesDir . '/PropertyPromotion/constructor_property_promotion.php.inc';
        
        if (!\file_exists($fixturePath)) {
            self::markTestSkipped('Property promotion fixture not found');
        }
        
        $content = \file_get_contents($fixturePath);
        $parts = \explode('-----', $content, 2);
        $before = $parts[0];
        $after = $parts[1];
        
        // Check that before code has traditional properties
        self::assertStringContainsString('private string $salt;', $before, 'Before code should have traditional property declaration');
        self::assertStringContainsString('$this->salt = $salt;', $before, 'Before code should have property assignment');
        
        // Check that after code uses property promotion
        self::assertStringContainsString('private string $salt', $after, 'After code should have promoted property');
        self::assertStringNotContainsString('$this->salt = $salt;', $after, 'After code should not have property assignment');
    }

    /**
     * Test that readonly properties transformation works correctly.
     */
    public function testReadOnlyPropertiesTransformation(): void
    {
        $fixturePath = $this->fixturesDir . '/ReadOnlyProperties/readonly_properties.php.inc';
        
        if (!\file_exists($fixturePath)) {
            self::markTestSkipped('Readonly properties fixture not found');
        }
        
        $content = \file_get_contents($fixturePath);
        $parts = \explode('-----', $content, 2);
        $before = $parts[0];
        $after = $parts[1];
        
        // Check that after code uses readonly modifier
        self::assertStringContainsString('readonly', $after, 'After code should contain readonly modifier');
        self::assertStringNotContainsString('readonly', $before, 'Before code should not contain readonly modifier');
    }

    /**
     * Test that Rector can process fixture files without errors.
     */
    public function testRectorProcessingWithFixtures(): void
    {
        if (!\file_exists($this->rectorBinary)) {
            self::markTestSkipped('Rector binary not found');
        }

        // Create a temporary test file
        $tempFile = \sys_get_temp_dir() . '/rector_test_' . \uniqid() . '.php';
        $testCode = "<?php\n\n";
        $testCode .= "namespace Test;\n\n";
        $testCode .= "class TestClass\n";
        $testCode .= "{\n";
        $testCode .= "    private string \$property;\n";
        $testCode .= "    \n";
        $testCode .= "    public function __construct(string \$property)\n";
        $testCode .= "    {\n";
        $testCode .= "        \$this->property = \$property;\n";
        $testCode .= "    }\n";
        $testCode .= "}\n";

        \file_put_contents($tempFile, $testCode);

        try {
            // Run Rector in dry-run mode
            $command = \sprintf(
                '%s process %s --dry-run --config=%s 2>&1',
                $this->rectorBinary,
                \escapeshellarg($tempFile),
                \escapeshellarg(__DIR__ . '/../../rector-php81.php')
            );

            $output = [];
            $returnCode = 0;
            \exec($command, $output, $returnCode);

            // Check that Rector runs without fatal errors
            self::assertNotEquals(255, $returnCode, 'Rector should not fail with fatal error');
            
            $outputString = \implode("\n", $output);
            self::assertStringNotContainsString('Fatal error', $outputString, 'Rector output should not contain fatal errors');
        } finally {
            // Clean up
            if (\file_exists($tempFile)) {
                \unlink($tempFile);
            }
        }
    }

    /**
     * Test that all expected Rector configuration files exist.
     */
    public function testRectorConfigurationFilesExist(): void
    {
        $configFiles = [
            'rector.php',
            'rector-php81.php',
            'rector-php82.php',
            'rector-php83.php',
            'rector-symfony.php',
            'rector-quality.php',
        ];

        $rootDir = __DIR__ . '/../..';
        
        foreach ($configFiles as $configFile) {
            $path = $rootDir . '/' . $configFile;
            self::assertFileExists($path, "Rector configuration file $configFile should exist");
        }
    }

    /**
     * Test that fixture transformations align with expected Rector rules.
     */
    public function testFixtureTransformationsMatchRectorRules(): void
    {
        $fixtures = [
            'AnnotationToAttribute' => [
                'pattern' => '@Hash',
                'replacement' => '#[Hash',
                'description' => 'Annotation to Attribute transformation',
            ],
            'PropertyPromotion' => [
                'pattern' => '$this->',
                'replacement' => 'private',
                'description' => 'Constructor property promotion',
            ],
            'ReadOnlyProperties' => [
                'pattern' => 'private',
                'replacement' => 'readonly',
                'description' => 'Readonly properties transformation',
            ],
        ];

        foreach ($fixtures as $dir => $expectations) {
            $fixtureDir = $this->fixturesDir . '/' . $dir;
            
            if (!\is_dir($fixtureDir)) {
                continue;
            }

            $finder = new Finder();
            $finder->files()->in($fixtureDir)->name('*.php.inc');

            foreach ($finder as $file) {
                $content = $file->getContents();
                $parts = \explode('-----', $content, 2);
                
                if (\count($parts) !== 2) {
                    continue;
                }
                
                $before = $parts[0];
                $after = $parts[1];

                // Basic validation that transformation occurred
                if (\str_contains($before, $expectations['pattern'])) {
                    self::assertStringContainsString(
                        $expectations['replacement'],
                        $after,
                        $expectations['description'] . ' should be applied in ' . $file->getFilename()
                    );
                }
            }
        }
    }
}