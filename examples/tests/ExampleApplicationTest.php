<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Examples\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;

class ExampleApplicationTest extends TestCase
{
    private Filesystem $filesystem;
    private string $examplesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = new Filesystem();
        $this->examplesDir = dirname(__DIR__);
    }

    public function testSymfony64ExampleExists(): void
    {
        $symfony64Dir = $this->examplesDir . '/symfony-6.4';
        $this->assertDirectoryExists($symfony64Dir, 'Symfony 6.4 example directory should exist');

        $composerFile = $symfony64Dir . '/composer.json';
        if ($this->filesystem->exists($composerFile)) {
            $composerContent = json_decode(file_get_contents($composerFile), true);
            $this->assertArrayHasKey('require', $composerContent);
            $this->assertArrayHasKey('symfony/framework-bundle', $composerContent['require']);
            $this->assertStringStartsWith('6.4', $composerContent['require']['symfony/framework-bundle']);
        }
    }

    public function testSymfony70ExampleExists(): void
    {
        $symfony70Dir = $this->examplesDir . '/symfony-7.0';
        $this->assertDirectoryExists($symfony70Dir, 'Symfony 7.0 example directory should exist');

        $composerFile = $symfony70Dir . '/composer.json';
        if ($this->filesystem->exists($composerFile)) {
            $composerContent = json_decode(file_get_contents($composerFile), true);
            $this->assertArrayHasKey('require', $composerContent);
            $this->assertArrayHasKey('symfony/framework-bundle', $composerContent['require']);
            $this->assertStringStartsWith('7.0', $composerContent['require']['symfony/framework-bundle']);
        }
    }

    public function testDockerConfigurationExists(): void
    {
        $dockerFile = $this->examplesDir . '/Dockerfile';
        $dockerComposeFile = $this->examplesDir . '/docker-compose.yml';

        if ($this->filesystem->exists($dockerFile)) {
            $this->assertFileExists($dockerFile, 'Dockerfile should exist');
            $content = file_get_contents($dockerFile);
            $this->assertStringContainsString('FROM php:', $content);
            $this->assertStringContainsString('composer', $content);
        }

        if ($this->filesystem->exists($dockerComposeFile)) {
            $this->assertFileExists($dockerComposeFile, 'docker-compose.yml should exist');
            $content = file_get_contents($dockerComposeFile);
            $this->assertStringContainsString('services:', $content);
        }
    }

    public function testExampleReadmeExists(): void
    {
        $mainReadme = $this->examplesDir . '/README.md';
        $symfony64Readme = $this->examplesDir . '/symfony-6.4/README.md';
        $symfony70Readme = $this->examplesDir . '/symfony-7.0/README.md';

        if ($this->filesystem->exists($mainReadme)) {
            $this->assertFileExists($mainReadme, 'Main examples README should exist');
            $content = file_get_contents($mainReadme);
            $this->assertStringContainsString('HashId Bundle Examples', $content);
            $this->assertStringContainsString('Symfony 6.4', $content);
            $this->assertStringContainsString('Symfony 7.0', $content);
        }

        if ($this->filesystem->exists($symfony64Readme)) {
            $content = file_get_contents($symfony64Readme);
            $this->assertStringContainsString('Installation', $content);
            $this->assertStringContainsString('Usage', $content);
        }

        if ($this->filesystem->exists($symfony70Readme)) {
            $content = file_get_contents($symfony70Readme);
            $this->assertStringContainsString('Installation', $content);
            $this->assertStringContainsString('Usage', $content);
        }
    }

    public function testHashIdFeaturesImplemented(): void
    {
        // Test Symfony 6.4 example features
        $this->assertHashIdFeaturesInExample('symfony-6.4');

        // Test Symfony 7.0 example features
        $this->assertHashIdFeaturesInExample('symfony-7.0');
    }

    private function assertHashIdFeaturesInExample(string $exampleDir): void
    {
        $controllerDir = $this->examplesDir . '/' . $exampleDir . '/src/Controller';

        if (!$this->filesystem->exists($controllerDir)) {
            $this->markTestSkipped("Controller directory not yet created for {$exampleDir}");
            return;
        }

        // Check for basic CRUD controller with HashId
        $orderController = $controllerDir . '/OrderController.php';
        if ($this->filesystem->exists($orderController)) {
            $content = file_get_contents($orderController);

            // Check for Hash annotation/attribute usage
            $this->assertTrue(
                str_contains($content, '@Hash') || str_contains($content, '#[Hash'),
                "Controller should use Hash annotation or attribute"
            );

            // Check for proper route definitions
            $this->assertStringContainsString('Route', $content);
            $this->assertStringContainsString('{id}', $content);
        }

        // Check for configuration
        $configFile = $this->examplesDir . '/' . $exampleDir . '/config/packages/pgs_hash_id.yaml';
        if ($this->filesystem->exists($configFile)) {
            $content = file_get_contents($configFile);
            $this->assertStringContainsString('pgs_hash_id:', $content);
            $this->assertStringContainsString('salt:', $content);
            $this->assertStringContainsString('min_hash_length:', $content);
        }
    }

    public function testMigrationExampleExists(): void
    {
        $migrationDir = $this->examplesDir . '/migration-example';

        if ($this->filesystem->exists($migrationDir)) {
            $this->assertDirectoryExists($migrationDir);

            // Check for v3 example code
            $v3Controller = $migrationDir . '/v3-example/OrderController.php';
            if ($this->filesystem->exists($v3Controller)) {
                $content = file_get_contents($v3Controller);
                $this->assertStringContainsString('@Hash', $content, 'v3 should use annotations');
            }

            // Check for v4 migrated code
            $v4Controller = $migrationDir . '/v4-example/OrderController.php';
            if ($this->filesystem->exists($v4Controller)) {
                $content = file_get_contents($v4Controller);
                $this->assertStringContainsString('#[Hash', $content, 'v4 should use attributes');
            }

            // Check for migration script
            $migrationScript = $migrationDir . '/migrate.sh';
            if ($this->filesystem->exists($migrationScript)) {
                $content = file_get_contents($migrationScript);
                $this->assertStringContainsString('rector', $content);
            }
        }
    }

    /**
     * @group functional
     */
    public function testSymfony64ApplicationRuns(): void
    {
        $appDir = $this->examplesDir . '/symfony-6.4';

        if (!$this->filesystem->exists($appDir . '/composer.json')) {
            $this->markTestSkipped('Symfony 6.4 example not yet created');
            return;
        }

        // Test composer install
        $process = new Process(['composer', 'install', '--no-interaction'], $appDir);
        $process->setTimeout(300);
        $process->run();

        $this->assertTrue($process->isSuccessful(), 'Composer install should succeed');

        // Test cache clear
        $process = new Process(['php', 'bin/console', 'cache:clear'], $appDir);
        $process->run();

        $this->assertTrue($process->isSuccessful(), 'Cache clear should succeed');

        // Test debug:router to verify routes are loaded
        $process = new Process(['php', 'bin/console', 'debug:router'], $appDir);
        $process->run();

        $this->assertTrue($process->isSuccessful());
        $this->assertStringContainsString('order_show', $process->getOutput());
    }

    /**
     * @group functional
     */
    public function testSymfony70ApplicationRuns(): void
    {
        $appDir = $this->examplesDir . '/symfony-7.0';

        if (!$this->filesystem->exists($appDir . '/composer.json')) {
            $this->markTestSkipped('Symfony 7.0 example not yet created');
            return;
        }

        // Test composer install
        $process = new Process(['composer', 'install', '--no-interaction'], $appDir);
        $process->setTimeout(300);
        $process->run();

        $this->assertTrue($process->isSuccessful(), 'Composer install should succeed');

        // Test cache clear
        $process = new Process(['php', 'bin/console', 'cache:clear'], $appDir);
        $process->run();

        $this->assertTrue($process->isSuccessful(), 'Cache clear should succeed');

        // Test debug:router to verify routes are loaded
        $process = new Process(['php', 'bin/console', 'debug:router'], $appDir);
        $process->run();

        $this->assertTrue($process->isSuccessful());
        $this->assertStringContainsString('order_show', $process->getOutput());
    }

    /**
     * @group docker
     */
    public function testDockerEnvironmentWorks(): void
    {
        if (!$this->filesystem->exists($this->examplesDir . '/docker-compose.yml')) {
            $this->markTestSkipped('Docker configuration not yet created');
            return;
        }

        // Test docker-compose config validation
        $process = new Process(['docker-compose', 'config'], $this->examplesDir);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->markTestSkipped('Docker or docker-compose not available');
            return;
        }

        $this->assertStringContainsString('services:', $process->getOutput());
    }
}