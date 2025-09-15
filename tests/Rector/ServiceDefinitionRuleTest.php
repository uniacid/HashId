<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Rector;

use Pgs\HashIdBundle\Rector\ServiceDefinitionRule;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ServiceDefinitionRuleTest extends AbstractRectorTestCase
{
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
        return $this->yieldFilesFromDirectory(__DIR__ . '/Fixtures/ServiceDefinition');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/service_definition_config.php';
    }
}