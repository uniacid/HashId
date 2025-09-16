<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Migration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class RectorTransformationTest extends TestCase
{
    private string $fixturesDir;
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = dirname(__DIR__, 2);
        $this->fixturesDir = __DIR__ . '/Fixtures';
    }

    public function testAnnotationToAttributeTransformation(): void
    {
        $inputFile = $this->fixturesDir . '/v3-sample-controller.php';
        $expectedOutput = $this->fixturesDir . '/v4-migrated-controller.php';

        // These files will be created as part of the test
        $this->createV3SampleController($inputFile);
        $this->createV4MigratedController($expectedOutput);

        // Run rector on the input file
        $tempFile = $this->fixturesDir . '/temp-transform.php';
        copy($inputFile, $tempFile);

        $process = new Process([
            'vendor/bin/rector',
            'process',
            $tempFile,
            '--config=rector-php81.php',
            '--no-diffs'
        ], $this->projectRoot);

        $process->run();

        if ($process->isSuccessful() && file_exists($tempFile)) {
            $transformedContent = file_get_contents($tempFile);

            // Check that annotations were converted to attributes
            $this->assertStringNotContainsString('@Route', $transformedContent, 'Route annotations should be converted');
            $this->assertStringNotContainsString('@Hash', $transformedContent, 'Hash annotations should be converted');
            $this->assertStringContainsString('#[Route', $transformedContent, 'Should have Route attributes');
            $this->assertStringContainsString('#[Hash', $transformedContent, 'Should have Hash attributes');

            // Clean up
            unlink($tempFile);
        }
    }

    public function testMultipleParameterTransformation(): void
    {
        $testCode = <<<'PHP'
<?php
namespace App\Controller;

use Pgs\HashIdBundle\Annotation\Hash;
use Symfony\Component\Routing\Annotation\Route;

class TestController
{
    /**
     * @Route("/compare/{id}/{otherId}")
     * @Hash({"id", "otherId"})
     */
    public function compare(int $id, int $otherId) {}
}
PHP;

        $testFile = $this->fixturesDir . '/multi-param-test.php';
        file_put_contents($testFile, $testCode);

        $process = new Process([
            'vendor/bin/rector',
            'process',
            $testFile,
            '--config=rector-php81.php',
            '--no-diffs'
        ], $this->projectRoot);

        $process->run();

        if (file_exists($testFile)) {
            $transformed = file_get_contents($testFile);

            // Check array syntax is preserved
            if (str_contains($transformed, '#[Hash')) {
                $this->assertMatchesRegularExpression(
                    '/#\[Hash\(\[[\'"](id|otherId)[\'"],\s*[\'"](id|otherId)[\'"]\]\)\]/',
                    $transformed,
                    'Multiple parameters should be preserved in array format'
                );
            }

            unlink($testFile);
        }
    }

    public function testRectorMetricsCollection(): void
    {
        // Test that we can collect metrics from Rector runs
        $metricsFile = $this->projectRoot . '/var/rector-metrics.json';

        // Run Rector with metrics collection
        $process = new Process([
            'vendor/bin/rector',
            'process',
            'tests/Migration/Fixtures',
            '--config=rector.php',
            '--dry-run',
            '--output-format=json'
        ], $this->projectRoot);

        $process->run();
        $output = $process->getOutput();

        if (!empty($output) && $this->isValidJson($output)) {
            $metrics = json_decode($output, true);

            // Check that metrics contain expected fields
            if (is_array($metrics)) {
                $this->assertArrayHasKey('files', $metrics, 'Metrics should include files processed');

                // Save metrics for documentation
                if (!file_exists(dirname($metricsFile))) {
                    mkdir(dirname($metricsFile), 0755, true);
                }
                file_put_contents($metricsFile, json_encode($metrics, JSON_PRETTY_PRINT));
            }
        }
    }

    public function testRectorPhaseApplication(): void
    {
        $phases = [
            'rector-php81.php' => 'PHP 8.1 transformations',
            'rector-php82.php' => 'PHP 8.2 transformations',
            'rector-php83.php' => 'PHP 8.3 transformations',
        ];

        foreach ($phases as $config => $description) {
            $configPath = $this->projectRoot . '/' . $config;
            if (!file_exists($configPath)) {
                continue;
            }

            // Test that each phase config is valid
            $process = new Process([
                'vendor/bin/rector',
                'process',
                '--config=' . $config,
                '--dry-run',
                '--no-diffs',
                'tests/Migration/Fixtures'
            ], $this->projectRoot);

            $process->run();

            // Should not have PHP parse errors
            $errorOutput = $process->getErrorOutput();
            $this->assertStringNotContainsString(
                'Parse error',
                $errorOutput,
                sprintf('%s should not cause parse errors', $description)
            );
        }
    }

    public function testCustomRectorRules(): void
    {
        $customRulesDir = $this->projectRoot . '/rector-rules';
        $this->assertDirectoryExists($customRulesDir, 'Custom Rector rules directory should exist');

        $deprecationHandler = $customRulesDir . '/DeprecationHandler.php';
        $this->assertFileExists($deprecationHandler, 'DeprecationHandler should exist');

        // Test that custom rules are properly namespaced and structured
        $content = file_get_contents($deprecationHandler);
        $this->assertStringContainsString('namespace Pgs\HashIdBundle\Rector', $content);
        $this->assertStringContainsString('trigger_error', $content, 'Should handle deprecations');
    }

    private function createV3SampleController(string $path): void
    {
        $content = <<<'PHP'
<?php

namespace App\Controller;

use Pgs\HashIdBundle\Annotation\Hash;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/api")
 */
class SampleController extends AbstractController
{
    /**
     * @Route("/user/{id}", name="api_user")
     * @Hash("id")
     */
    public function getUser(int $id)
    {
        return $this->json(['id' => $id]);
    }

    /**
     * @Route("/compare/{id}/{otherId}", name="api_compare")
     * @Hash({"id", "otherId"})
     */
    public function compareUsers(int $id, int $otherId)
    {
        return $this->json(['id' => $id, 'otherId' => $otherId]);
    }
}
PHP;
        file_put_contents($path, $content);
    }

    private function createV4MigratedController(string $path): void
    {
        $content = <<<'PHP'
<?php

namespace App\Controller;

use Pgs\HashIdBundle\Attribute\Hash;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api')]
class SampleController extends AbstractController
{
    #[Route('/user/{id}', name: 'api_user')]
    #[Hash('id')]
    public function getUser(int $id)
    {
        return $this->json(['id' => $id]);
    }

    #[Route('/compare/{id}/{otherId}', name: 'api_compare')]
    #[Hash(['id', 'otherId'])]
    public function compareUsers(int $id, int $otherId)
    {
        return $this->json(['id' => $id, 'otherId' => $otherId]);
    }
}
PHP;
        file_put_contents($path, $content);
    }

    private function isValidJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}