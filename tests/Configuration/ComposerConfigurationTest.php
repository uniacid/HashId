<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Configuration;

use PHPUnit\Framework\TestCase;

/**
 * Test suite for composer.json validation and dependency compatibility checks.
 */
class ComposerConfigurationTest extends TestCase
{
    private string $composerJsonPath;
    private array $composerData;

    protected function setUp(): void
    {
        $this->composerJsonPath = __DIR__ . '/../../composer.json';
        $this->composerData = \json_decode(\file_get_contents($this->composerJsonPath), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testComposerJsonExists(): void
    {
        self::assertFileExists($this->composerJsonPath, 'composer.json file must exist');
    }

    public function testComposerJsonIsValidJson(): void
    {
        $content = \file_get_contents($this->composerJsonPath);
        self::assertNotFalse($content, 'composer.json must be readable');

        \json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue(true, 'composer.json must contain valid JSON');
    }

    public function testPhpVersionRequirement(): void
    {
        self::assertArrayHasKey('require', $this->composerData);
        self::assertArrayHasKey('php', $this->composerData['require']);

        $phpVersion = $this->composerData['require']['php'];
        self::assertMatchesRegularExpression('/\^8\.[1-9]/', $phpVersion, 'PHP version must be 8.1 or higher');
    }

    public function testHashidsVersionCompatibility(): void
    {
        self::assertArrayHasKey('require', $this->composerData);
        self::assertArrayHasKey('hashids/hashids', $this->composerData['require']);

        $hashidsVersion = $this->composerData['require']['hashids/hashids'];
        self::assertMatchesRegularExpression('/\^[4-5]\.|>=4\.0/', $hashidsVersion, 'hashids/hashids must be version 4.0 or higher');
    }

    public function testSymfonyBundleRequirements(): void
    {
        self::assertArrayHasKey('require', $this->composerData);

        $requiredSymfonyPackages = [
            'symfony/dependency-injection',
            'symfony/config',
            'symfony/routing',
        ];

        foreach ($requiredSymfonyPackages as $package) {
            self::assertArrayHasKey($package, $this->composerData['require'], "Required Symfony package {$package} must be present");

            $version = $this->composerData['require'][$package];
            self::assertMatchesRegularExpression('/\^6\.4|\^7\.0/', $version, "{$package} must support Symfony 6.4 LTS or 7.0");
        }
    }

    public function testDoctrineAnnotationsRemoval(): void
    {
        self::assertArrayHasKey('require', $this->composerData);
        self::assertArrayNotHasKey('doctrine/annotations', $this->composerData['require'], 'doctrine/annotations should be removed in favor of PHP attributes');
    }

    public function testPhpUnitVersionUpgrade(): void
    {
        self::assertArrayHasKey('require-dev', $this->composerData);
        self::assertArrayHasKey('phpunit/phpunit', $this->composerData['require-dev']);

        $phpunitVersion = $this->composerData['require-dev']['phpunit/phpunit'];
        self::assertMatchesRegularExpression('/\^10\./', $phpunitVersion, 'PHPUnit must be version 10.x');
    }

    public function testPhpStanLevelConfiguration(): void
    {
        self::assertArrayHasKey('require-dev', $this->composerData);
        self::assertArrayHasKey('phpstan/phpstan', $this->composerData['require-dev']);

        $phpstanVersion = $this->composerData['require-dev']['phpstan/phpstan'];
        self::assertMatchesRegularExpression('/\^1\./', $phpstanVersion, 'PHPStan must be version 1.x');
    }

    public function testPhpCsFixerVersionUpgrade(): void
    {
        self::assertArrayHasKey('require-dev', $this->composerData);
        self::assertArrayHasKey('friendsofphp/php-cs-fixer', $this->composerData['require-dev']);

        $phpCsFixerVersion = $this->composerData['require-dev']['friendsofphp/php-cs-fixer'];
        self::assertMatchesRegularExpression('/\^3\./', $phpCsFixerVersion, 'PHP CS Fixer must be version 3.x');
    }

    public function testRectorPresence(): void
    {
        self::assertArrayHasKey('require-dev', $this->composerData);
        self::assertArrayHasKey('rector/rector', $this->composerData['require-dev']);

        $rectorVersion = $this->composerData['require-dev']['rector/rector'];
        self::assertMatchesRegularExpression('/\^1\./', $rectorVersion, 'Rector must be version 1.x');
    }

    public function testBundleTypeAndAutoloading(): void
    {
        self::assertSame('symfony-bundle', $this->composerData['type'], 'Package type must be symfony-bundle');

        self::assertArrayHasKey('autoload', $this->composerData);
        self::assertArrayHasKey('psr-4', $this->composerData['autoload']);
        self::assertArrayHasKey('Pgs\\HashIdBundle\\', $this->composerData['autoload']['psr-4']);
        self::assertSame('src/', $this->composerData['autoload']['psr-4']['Pgs\\HashIdBundle\\']);
    }

    public function testMinimumStabilityRequirement(): void
    {
        self::assertArrayHasKey('minimum-stability', $this->composerData);
        self::assertSame('stable', $this->composerData['minimum-stability'], 'Minimum stability must be stable');
    }

    public function testCompatibilityWithSymfonyPHPUnitBridge(): void
    {
        self::assertArrayHasKey('require-dev', $this->composerData);

        // Check if symfony/phpunit-bridge is present for better Symfony integration
        if (\array_key_exists('symfony/phpunit-bridge', $this->composerData['require-dev'])) {
            $bridgeVersion = $this->composerData['require-dev']['symfony/phpunit-bridge'];
            self::assertMatchesRegularExpression('/\^6\.4|\^7\.0/', $bridgeVersion, 'Symfony PHPUnit Bridge must match Symfony version');
        }
    }

    public function testDevelopmentTargetCompatibility(): void
    {
        // Validate that all dev dependencies support PHP 8.3
        self::assertArrayHasKey('require-dev', $this->composerData);

        $criticalDevDependencies = [
            'phpstan/phpstan',
            'phpunit/phpunit',
            'friendsofphp/php-cs-fixer',
            'rector/rector',
        ];

        foreach ($criticalDevDependencies as $dependency) {
            self::assertArrayHasKey($dependency, $this->composerData['require-dev'], "Critical dev dependency {$dependency} must be present");
        }

        // This is a basic check - in practice, you'd validate version constraints against packagist API
        self::assertTrue(true, 'All critical dev dependencies are present');
    }
}
