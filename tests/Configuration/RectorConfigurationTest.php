<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Configuration;

use PHPUnit\Framework\TestCase;
use Rector\Config\RectorConfig;

class RectorConfigurationTest extends TestCase
{
    private string $rootDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__, 2);
    }

    public function testMainRectorConfigurationExists(): void
    {
        $configPath = $this->rootDir . '/rector.php';
        $this->assertFileExists($configPath, 'Main rector.php configuration file should exist');
    }

    public function testPhp81ConfigurationExists(): void
    {
        $configPath = $this->rootDir . '/rector-php81.php';
        $this->assertFileExists($configPath, 'rector-php81.php configuration file should exist');
    }

    public function testSymfonyConfigurationExists(): void
    {
        $configPath = $this->rootDir . '/rector-symfony.php';
        $this->assertFileExists($configPath, 'rector-symfony.php configuration file should exist');
    }

    public function testQualityConfigurationExists(): void
    {
        $configPath = $this->rootDir . '/rector-quality.php';
        $this->assertFileExists($configPath, 'rector-quality.php configuration file should exist');
    }

    public function testPhp82PlaceholderExists(): void
    {
        $configPath = $this->rootDir . '/rector-php82.php';
        $this->assertFileExists($configPath, 'rector-php82.php placeholder should exist');
    }

    public function testPhp83PlaceholderExists(): void
    {
        $configPath = $this->rootDir . '/rector-php83.php';
        $this->assertFileExists($configPath, 'rector-php83.php placeholder should exist');
    }

    public function testMainConfigurationLoadsWithoutErrors(): void
    {
        $configPath = $this->rootDir . '/rector.php';
        
        if (!file_exists($configPath)) {
            $this->markTestSkipped('rector.php does not exist yet');
        }

        $this->expectNotToPerformAssertions();
        
        // Test that the configuration file can be loaded without PHP errors
        $config = require $configPath;
        $this->assertIsCallable($config, 'rector.php should return a callable configuration');
    }

    public function testPhp81ConfigurationLoadsWithoutErrors(): void
    {
        $configPath = $this->rootDir . '/rector-php81.php';
        
        if (!file_exists($configPath)) {
            $this->markTestSkipped('rector-php81.php does not exist yet');
        }

        $this->expectNotToPerformAssertions();
        
        // Test that the configuration file can be loaded without PHP errors
        $config = require $configPath;
        $this->assertIsCallable($config, 'rector-php81.php should return a callable configuration');
    }

    public function testSymfonyConfigurationLoadsWithoutErrors(): void
    {
        $configPath = $this->rootDir . '/rector-symfony.php';
        
        if (!file_exists($configPath)) {
            $this->markTestSkipped('rector-symfony.php does not exist yet');
        }

        $this->expectNotToPerformAssertions();
        
        // Test that the configuration file can be loaded without PHP errors
        $config = require $configPath;
        $this->assertIsCallable($config, 'rector-symfony.php should return a callable configuration');
    }

    public function testQualityConfigurationLoadsWithoutErrors(): void
    {
        $configPath = $this->rootDir . '/rector-quality.php';
        
        if (!file_exists($configPath)) {
            $this->markTestSkipped('rector-quality.php does not exist yet');
        }

        $this->expectNotToPerformAssertions();
        
        // Test that the configuration file can be loaded without PHP errors
        $config = require $configPath;
        $this->assertIsCallable($config, 'rector-quality.php should return a callable configuration');
    }

    public function testDryRunScript(): void
    {
        $scriptPath = $this->rootDir . '/bin/rector-dry-run.sh';
        
        if (!file_exists($scriptPath)) {
            $this->markTestSkipped('rector-dry-run.sh does not exist yet');
        }

        $this->assertFileExists($scriptPath, 'Dry-run script should exist');
        $this->assertTrue(is_executable($scriptPath), 'Dry-run script should be executable');
    }

    /**
     * Test that rector configurations don't contain syntax errors
     */
    public function testConfigurationSyntaxValidity(): void
    {
        $configFiles = [
            'rector.php',
            'rector-php81.php', 
            'rector-symfony.php',
            'rector-quality.php',
            'rector-php82.php',
            'rector-php83.php'
        ];

        foreach ($configFiles as $configFile) {
            $configPath = $this->rootDir . '/' . $configFile;
            
            if (!file_exists($configPath)) {
                continue; // Skip if file doesn't exist yet
            }

            // Check syntax by attempting to parse the file
            $output = [];
            $returnCode = 0;
            exec("php -l {$configPath} 2>&1", $output, $returnCode);
            
            $this->assertSame(0, $returnCode, 
                "Configuration file {$configFile} should have valid PHP syntax. Output: " . implode("\n", $output)
            );
        }
    }

    /**
     * Test that source and test directories exist for Rector processing
     */
    public function testRequiredDirectoriesExist(): void
    {
        $this->assertDirectoryExists($this->rootDir . '/src', 'Source directory should exist');
        $this->assertDirectoryExists($this->rootDir . '/tests', 'Tests directory should exist');
    }
}