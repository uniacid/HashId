<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Rector;

use PHPUnit\Framework\TestCase;
use Rector\Testing\PHPUnit\AbstractRectorTestCase as BaseRectorTestCase;
use Rector\Config\RectorConfig;

/**
 * Base test class for HashId Bundle Rector rules.
 */
abstract class AbstractRectorTestCase extends BaseRectorTestCase
{
    /**
     * Get the Rector rule class being tested.
     */
    abstract protected function getRectorClass(): string;

    /**
     * Configure Rector for testing.
     */
    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }

    /**
     * Get fixture directory path.
     */
    protected function getFixtureDirectory(): string
    {
        $rectorClass = $this->getRectorClass();
        $className = substr($rectorClass, strrpos($rectorClass, '\\') + 1);
        $ruleName = str_replace('Rule', '', $className);
        
        return __DIR__ . '/Fixtures/' . $ruleName;
    }

    /**
     * @dataProvider provideData
     */
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    /**
     * @return \Iterator<array<string>>
     */
    public function provideData(): \Iterator
    {
        return $this->yieldFilesFromDirectory($this->getFixtureDirectory());
    }
}