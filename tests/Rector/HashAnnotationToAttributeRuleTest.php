<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Rector;

use Pgs\HashIdBundle\Rector\HashAnnotationToAttributeRule;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class HashAnnotationToAttributeRuleTest extends AbstractRectorTestCase
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
        return $this->yieldFilesFromDirectory(__DIR__ . '/Fixtures/HashAnnotationToAttribute');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/hash_annotation_config.php';
    }
}