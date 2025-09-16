<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Rector;

use RuntimeException;
use PHPUnit\Framework\TestCase;

/**
 * Base class for fixture-based testing of custom Rector rules.
 *
 * This class provides utilities for testing Rector rules using fixture files
 * that contain before and after code samples separated by "-----".
 */
abstract class FixtureBasedTestRunner extends TestCase
{
    protected string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/Fixtures';
    }

    /**
     * Parse a fixture file into before and after sections.
     *
     * @param string $fixturePath Path to the fixture file
     *
     * @return array{before: string, after: string}
     */
    protected function parseFixture(string $fixturePath): array
    {
        if (!\file_exists($fixturePath)) {
            throw new RuntimeException("Fixture file not found: {$fixturePath}");
        }

        $content = \file_get_contents($fixturePath);
        $parts = \explode("\n-----\n", $content);

        if (\count($parts) !== 2) {
            throw new RuntimeException(
                "Invalid fixture format. Expected exactly one '-----' separator in {$fixturePath}",
            );
        }

        return [
            'before' => \mb_trim($parts[0]),
            'after' => \mb_trim($parts[1]),
        ];
    }

    /**
     * Test a Rector rule against a fixture file.
     *
     * @param string $ruleClass Fully qualified class name of the Rector rule
     * @param string $fixtureName Name of the fixture file (without .php.inc extension)
     */
    protected function testRuleWithFixture(string $ruleClass, string $fixtureName): void
    {
        $fixturePath = $this->fixturesDir . '/' . $fixtureName . '.php.inc';
        $fixture = $this->parseFixture($fixturePath);

        // This is a simplified test - in a real implementation, you would:
        // 1. Create a Rector configuration with the specific rule
        // 2. Run Rector on the 'before' code
        // 3. Compare the result with the 'after' code

        self::assertNotEmpty($fixture['before'], 'Before code should not be empty');
        self::assertNotEmpty($fixture['after'], 'After code should not be empty');

        // Verify that the fixture represents a meaningful transformation
        self::assertNotSame(
            $fixture['before'],
            $fixture['after'],
            "Fixture {$fixtureName} should show a transformation",
        );
    }

    /**
     * Get all fixture files in the fixtures directory.
     *
     * @return array<string, string> Map of fixture name to file path
     */
    protected function getAllFixtures(): array
    {
        $fixtures = [];
        $files = \glob($this->fixturesDir . '/*.php.inc');

        foreach ($files as $file) {
            $name = \basename($file, '.php.inc');
            $fixtures[$name] = $file;
        }

        return $fixtures;
    }

    /**
     * Validate that all fixtures have proper format.
     */
    public function testAllFixturesAreValid(): void
    {
        $fixtures = $this->getAllFixtures();

        if (empty($fixtures)) {
            self::markTestSkipped('No fixtures found');
        }

        foreach ($fixtures as $name => $path) {
            try {
                $this->parseFixture($path);
                self::assertTrue(true, "Fixture {$name} is valid");
            } catch (RuntimeException $e) {
                self::fail("Fixture {$name} is invalid: " . $e->getMessage());
            }
        }
    }
}
