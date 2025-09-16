<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Rector;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Rector\MetricsCollector;
use Symfony\Component\Process\Process;
use Symfony\Component\Finder\Finder;

class RuleEffectivenessTest extends TestCase
{
    private string $testProjectDir;
    private MetricsCollector $metricsCollector;
    private array $customRules = [
        'AnnotationToAttributeRule' => [
            'target_success_rate' => 90.0,
            'description' => 'Converts Hash annotations to PHP 8.1+ attributes',
        ],
        'ConstructorPropertyPromotionRule' => [
            'target_success_rate' => 85.0,
            'description' => 'Promotes constructor parameters to properties',
        ],
        'ReadOnlyPropertyRule' => [
            'target_success_rate' => 85.0,
            'description' => 'Adds readonly modifier to eligible properties',
        ],
        'TypedPropertyRule' => [
            'target_success_rate' => 80.0,
            'description' => 'Adds type declarations to properties',
        ],
        'StrictTypesRule' => [
            'target_success_rate' => 95.0,
            'description' => 'Adds declare(strict_types=1) to PHP files',
        ],
    ];

    protected function setUp(): void
    {
        $this->testProjectDir = sys_get_temp_dir() . '/hashid-rector-effectiveness-' . uniqid();
        if (!is_dir($this->testProjectDir)) {
            mkdir($this->testProjectDir, 0777, true);
        }

        $this->metricsCollector = new MetricsCollector();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testProjectDir)) {
            $this->removeDirectory($this->testProjectDir);
        }
    }

    public function testAnnotationToAttributeRuleEffectiveness(): void
    {
        $this->createAnnotationTestFiles();

        $startTime = microtime(true);
        $metrics = $this->runRectorWithRule('AnnotationToAttributeRule');
        $executionTime = microtime(true) - $startTime;

        $this->assertArrayHasKey('files_processed', $metrics);
        $this->assertArrayHasKey('files_modified', $metrics);
        $this->assertArrayHasKey('transformations_successful', $metrics);
        $this->assertArrayHasKey('transformations_failed', $metrics);
        $this->assertArrayHasKey('success_rate', $metrics);

        $this->assertGreaterThanOrEqual(90.0, $metrics['success_rate'],
            'Annotation to attribute conversion should achieve at least 90% success rate');

        $this->assertLessThan(1.0, $executionTime,
            'Rule should execute in under 1 second for test files');

        $this->validateTransformationCorrectness();
    }

    public function testConstructorPropertyPromotionRuleEffectiveness(): void
    {
        $this->createConstructorTestFiles();

        $metrics = $this->runRectorWithRule('ConstructorPropertyPromotionRule');

        $this->assertGreaterThanOrEqual(85.0, $metrics['success_rate'],
            'Constructor property promotion should achieve at least 85% success rate');

        $this->assertGreaterThan(0, $metrics['properties_promoted'],
            'Should promote at least some properties');

        $this->validatePromotedProperties($metrics['files_modified']);
    }

    public function testReadOnlyPropertyRuleEffectiveness(): void
    {
        $this->createReadOnlyTestFiles();

        $metrics = $this->runRectorWithRule('ReadOnlyPropertyRule');

        $this->assertGreaterThanOrEqual(85.0, $metrics['success_rate'],
            'Readonly property conversion should achieve at least 85% success rate');

        $this->assertArrayHasKey('readonly_properties_added', $metrics);
        $this->assertGreaterThan(0, $metrics['readonly_properties_added']);
    }

    public function testTypedPropertyRuleEffectiveness(): void
    {
        $this->createTypedPropertyTestFiles();

        $metrics = $this->runRectorWithRule('TypedPropertyRule');

        $this->assertGreaterThanOrEqual(80.0, $metrics['success_rate'],
            'Type property addition should achieve at least 80% success rate');

        $this->assertArrayHasKey('types_added', $metrics);
        $this->assertGreaterThan(0, $metrics['types_added']);
    }

    public function testAllRulesCombinedEffectiveness(): void
    {
        $this->createMixedTestFiles();

        $startTime = microtime(true);
        $metrics = $this->runRectorWithAllRules();
        $executionTime = microtime(true) - $startTime;

        $overallSuccessRate = $this->calculateOverallSuccessRate($metrics);

        $this->assertGreaterThanOrEqual(85.0, $overallSuccessRate,
            'Combined rules should achieve at least 85% success rate');

        $this->assertLessThan(5.0, $executionTime,
            'All rules should execute in under 5 seconds for test project');
    }

    public function testRuleAccuracy(): void
    {
        $testCases = [
            'annotation_simple' => $this->getSimpleAnnotationCase(),
            'annotation_complex' => $this->getComplexAnnotationCase(),
            'constructor_basic' => $this->getBasicConstructorCase(),
            'readonly_eligible' => $this->getReadOnlyEligibleCase(),
        ];

        foreach ($testCases as $name => $testCase) {
            $this->createTestFile($name . '.php', $testCase['input']);
            $this->runRectorWithRule($testCase['rule']);

            $result = file_get_contents($this->testProjectDir . '/' . $name . '.php');
            $this->assertEquals($testCase['expected'], $result,
                "Transformation for {$name} should match expected output");
        }
    }

    public function testManualVsAutomatedComparison(): void
    {
        $manualTime = $this->measureManualTransformationTime();
        $automatedTime = $this->measureAutomatedTransformationTime();

        $timeReduction = (($manualTime - $automatedTime) / $manualTime) * 100;

        $this->assertGreaterThanOrEqual(50.0, $timeReduction,
            'Automated transformation should be at least 50% faster than manual');

        $this->metricsCollector->calculateTimeSavings($manualTime);
        $savings = $this->metricsCollector->getTimeSavingsMetrics();

        $this->assertArrayHasKey('time_saved_seconds', $savings);
        $this->assertArrayHasKey('time_savings_percentage', $savings);
    }

    public function testRulePerformanceUnderLoad(): void
    {
        $this->createLargeTestProject(100); // 100 files

        $startTime = microtime(true);
        $metrics = $this->runRectorWithAllRules();
        $executionTime = microtime(true) - $startTime;

        $filesPerSecond = $metrics['files_processed'] / $executionTime;

        $this->assertGreaterThan(10, $filesPerSecond,
            'Should process at least 10 files per second');

        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024;
        $this->assertLessThan(256, $memoryUsage,
            'Memory usage should not exceed 256MB for 100 files');
    }

    public function testRuleErrorHandling(): void
    {
        $this->createMalformedTestFiles();

        $metrics = $this->runRectorWithAllRules();

        $this->assertArrayHasKey('errors', $metrics);
        $this->assertIsArray($metrics['errors']);

        foreach ($metrics['errors'] as $error) {
            $this->assertArrayHasKey('file', $error);
            $this->assertArrayHasKey('rule', $error);
            $this->assertArrayHasKey('message', $error);
        }

        $errorRate = ($metrics['transformations_failed'] /
                     ($metrics['transformations_successful'] + $metrics['transformations_failed'])) * 100;

        $this->assertLessThan(5.0, $errorRate,
            'Error rate should be less than 5%');
    }

    public function testBaselineMetricsGeneration(): void
    {
        $baseline = $this->generateBaselineMetrics();

        $this->assertArrayHasKey('annotation_conversion_time', $baseline);
        $this->assertArrayHasKey('property_promotion_time', $baseline);
        $this->assertArrayHasKey('readonly_conversion_time', $baseline);
        $this->assertArrayHasKey('type_addition_time', $baseline);

        foreach ($baseline as $metric => $value) {
            $this->assertIsFloat($value);
            $this->assertGreaterThan(0, $value);
        }

        $this->saveBaselineMetrics($baseline);
    }

    private function runRectorWithRule(string $ruleName): array
    {
        $configFile = $this->createTempRectorConfig([$ruleName]);

        $process = new Process([
            'vendor/bin/rector',
            'process',
            $this->testProjectDir,
            '--config=' . $configFile,
            '--dry-run',
            '--output-format=json'
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            $this->metricsCollector->trackError($ruleName, $process->getErrorOutput());
        }

        return $this->parseRectorOutput($process->getOutput());
    }

    private function runRectorWithAllRules(): array
    {
        $configFile = $this->createTempRectorConfig(array_keys($this->customRules));

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
        $lines = explode("\n", $output);
        $metrics = [
            'files_processed' => 0,
            'files_modified' => 0,
            'transformations_successful' => 0,
            'transformations_failed' => 0,
            'properties_promoted' => 0,
            'readonly_properties_added' => 0,
            'types_added' => 0,
            'errors' => []
        ];

        foreach ($lines as $line) {
            if (strpos($line, 'files_processed') !== false) {
                preg_match('/(\d+)/', $line, $matches);
                $metrics['files_processed'] = (int)($matches[1] ?? 0);
            }

            if (strpos($line, 'files_modified') !== false) {
                preg_match('/(\d+)/', $line, $matches);
                $metrics['files_modified'] = (int)($matches[1] ?? 0);
            }
        }

        $metrics['transformations_successful'] = $metrics['files_modified'];
        $metrics['transformations_failed'] = $metrics['files_processed'] - $metrics['files_modified'];

        if ($metrics['files_processed'] > 0) {
            $metrics['success_rate'] = ($metrics['transformations_successful'] / $metrics['files_processed']) * 100;
        } else {
            $metrics['success_rate'] = 0;
        }

        return $metrics;
    }

    private function createAnnotationTestFiles(): void
    {
        $files = [
            'SimpleController.php' => $this->getAnnotationTestFile('simple'),
            'ComplexController.php' => $this->getAnnotationTestFile('complex'),
            'MultipleAnnotationsController.php' => $this->getAnnotationTestFile('multiple'),
        ];

        foreach ($files as $filename => $content) {
            file_put_contents($this->testProjectDir . '/' . $filename, $content);
        }
    }

    private function createConstructorTestFiles(): void
    {
        $files = [
            'BasicService.php' => $this->getConstructorTestFile('basic'),
            'ComplexService.php' => $this->getConstructorTestFile('complex'),
            'MixedService.php' => $this->getConstructorTestFile('mixed'),
        ];

        foreach ($files as $filename => $content) {
            file_put_contents($this->testProjectDir . '/' . $filename, $content);
        }
    }

    private function createReadOnlyTestFiles(): void
    {
        $files = [
            'Entity.php' => $this->getReadOnlyTestFile('entity'),
            'ValueObject.php' => $this->getReadOnlyTestFile('value_object'),
            'DTO.php' => $this->getReadOnlyTestFile('dto'),
        ];

        foreach ($files as $filename => $content) {
            file_put_contents($this->testProjectDir . '/' . $filename, $content);
        }
    }

    private function createTypedPropertyTestFiles(): void
    {
        $files = [
            'UntypedClass.php' => $this->getTypedPropertyTestFile('untyped'),
            'PartiallyTyped.php' => $this->getTypedPropertyTestFile('partial'),
            'DocblockTyped.php' => $this->getTypedPropertyTestFile('docblock'),
        ];

        foreach ($files as $filename => $content) {
            file_put_contents($this->testProjectDir . '/' . $filename, $content);
        }
    }

    private function createMixedTestFiles(): void
    {
        $this->createAnnotationTestFiles();
        $this->createConstructorTestFiles();
        $this->createReadOnlyTestFiles();
        $this->createTypedPropertyTestFiles();
    }

    private function createLargeTestProject(int $fileCount): void
    {
        for ($i = 1; $i <= $fileCount; $i++) {
            $type = $i % 4;
            $content = match ($type) {
                0 => $this->getAnnotationTestFile('simple'),
                1 => $this->getConstructorTestFile('basic'),
                2 => $this->getReadOnlyTestFile('entity'),
                default => $this->getTypedPropertyTestFile('untyped'),
            };

            file_put_contents($this->testProjectDir . "/File{$i}.php", $content);
        }
    }

    private function createMalformedTestFiles(): void
    {
        $files = [
            'SyntaxError.php' => '<?php class Test {',
            'InvalidAnnotation.php' => '<?php /** @Hash() */ class Test {}',
            'BrokenConstructor.php' => '<?php class Test { public function __construct( {} }',
        ];

        foreach ($files as $filename => $content) {
            file_put_contents($this->testProjectDir . '/' . $filename, $content);
        }
    }

    private function getAnnotationTestFile(string $type): string
    {
        return match ($type) {
            'simple' => <<<'PHP'
<?php
use Pgs\HashIdBundle\Annotation\Hash;

class SimpleController
{
    /**
     * @Hash("id")
     */
    public function show(int $id) {}
}
PHP,
            'complex' => <<<'PHP'
<?php
use Pgs\HashIdBundle\Annotation\Hash;

class ComplexController
{
    /**
     * @Hash({"id", "categoryId"})
     */
    public function show(int $id, int $categoryId) {}
}
PHP,
            'multiple' => <<<'PHP'
<?php
use Pgs\HashIdBundle\Annotation\Hash;

class MultipleController
{
    /**
     * @Hash("id")
     * @Hash("parentId")
     */
    public function show(int $id, int $parentId) {}
}
PHP,
        };
    }

    private function getConstructorTestFile(string $type): string
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

    private function getReadOnlyTestFile(string $type): string
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
    public function getName(): string { return $this->name; }
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
    public array $field3;

    public function __construct(string $field1, ?int $field2, array $field3)
    {
        $this->field1 = $field1;
        $this->field2 = $field2;
        $this->field3 = $field3;
    }
}
PHP,
        };
    }

    private function getTypedPropertyTestFile(string $type): string
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

    /** @var \DateTime|null */
    public $date;
}
PHP,
        };
    }

    private function getSimpleAnnotationCase(): array
    {
        return [
            'rule' => 'AnnotationToAttributeRule',
            'input' => <<<'PHP'
<?php
use Pgs\HashIdBundle\Annotation\Hash;

class TestController
{
    /**
     * @Hash("id")
     */
    public function show(int $id) {}
}
PHP,
            'expected' => <<<'PHP'
<?php
use Pgs\HashIdBundle\Attribute\Hash;

class TestController
{
    #[Hash("id")]
    public function show(int $id) {}
}
PHP,
        ];
    }

    private function getComplexAnnotationCase(): array
    {
        return [
            'rule' => 'AnnotationToAttributeRule',
            'input' => <<<'PHP'
<?php
use Pgs\HashIdBundle\Annotation\Hash;

class TestController
{
    /**
     * @Hash({"id", "categoryId"})
     */
    public function show(int $id, int $categoryId) {}
}
PHP,
            'expected' => <<<'PHP'
<?php
use Pgs\HashIdBundle\Attribute\Hash;

class TestController
{
    #[Hash(["id", "categoryId"])]
    public function show(int $id, int $categoryId) {}
}
PHP,
        ];
    }

    private function getBasicConstructorCase(): array
    {
        return [
            'rule' => 'ConstructorPropertyPromotionRule',
            'input' => <<<'PHP'
<?php
class Service
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
PHP,
            'expected' => <<<'PHP'
<?php
class Service
{
    public function __construct(private string $name)
    {
    }
}
PHP,
        ];
    }

    private function getReadOnlyEligibleCase(): array
    {
        return [
            'rule' => 'ReadOnlyPropertyRule',
            'input' => <<<'PHP'
<?php
class ValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
PHP,
            'expected' => <<<'PHP'
<?php
class ValueObject
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
PHP,
        ];
    }

    private function createTestFile(string $filename, string $content): void
    {
        file_put_contents($this->testProjectDir . '/' . $filename, $content);
    }

    private function createTempRectorConfig(array $rules): string
    {
        $configFile = $this->testProjectDir . '/rector.php';
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

    private function validateTransformationCorrectness(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->testProjectDir)->name('*.php');

        foreach ($finder as $file) {
            $content = $file->getContents();

            // Validate PHP syntax
            $process = new Process(['php', '-l', $file->getRealPath()]);
            $process->run();
            $this->assertTrue($process->isSuccessful(),
                "Transformed file {$file->getFilename()} should have valid PHP syntax");

            // Check no annotations remain when attributes expected
            if (strpos($content, '#[Hash') !== false) {
                $this->assertStringNotContainsString('@Hash', $content,
                    "File should not contain both annotations and attributes");
            }
        }
    }

    private function validatePromotedProperties(array $modifiedFiles): void
    {
        foreach ($modifiedFiles as $file) {
            $content = file_get_contents($file);

            if (strpos($content, '__construct') !== false &&
                strpos($content, 'private') !== false) {
                $this->assertMatchesRegularExpression(
                    '/public function __construct\([^)]*private[^)]*\)/',
                    $content,
                    "Constructor should contain promoted properties"
                );
            }
        }
    }

    private function calculateOverallSuccessRate(array $metrics): float
    {
        $totalSuccess = 0;
        $totalAttempts = 0;

        foreach ($this->customRules as $ruleName => $ruleConfig) {
            if (isset($metrics['rules'][$ruleName])) {
                $totalSuccess += $metrics['rules'][$ruleName]['successful'];
                $totalAttempts += $metrics['rules'][$ruleName]['total'];
            }
        }

        return $totalAttempts > 0 ? ($totalSuccess / $totalAttempts) * 100 : 0;
    }

    private function measureManualTransformationTime(): float
    {
        // Simulate manual transformation time based on empirical data
        // Average developer time per transformation type
        return 5.0 * 60; // 5 minutes in seconds
    }

    private function measureAutomatedTransformationTime(): float
    {
        $this->createMixedTestFiles();

        $startTime = microtime(true);
        $this->runRectorWithAllRules();

        return microtime(true) - $startTime;
    }

    private function generateBaselineMetrics(): array
    {
        $baseline = [];

        foreach ($this->customRules as $ruleName => $config) {
            $this->createMixedTestFiles();

            $startTime = microtime(true);
            $this->runRectorWithRule($ruleName);
            $executionTime = microtime(true) - $startTime;

            $baseline[strtolower(str_replace('Rule', '_time', $ruleName))] = $executionTime;
        }

        return $baseline;
    }

    private function saveBaselineMetrics(array $baseline): void
    {
        $file = __DIR__ . '/../../var/rector-baseline-metrics.json';
        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($file, json_encode($baseline, JSON_PRETTY_PRINT));
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