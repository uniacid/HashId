<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Traits;

use BadMethodCallException;
use Pgs\HashIdBundle\Tests\Traits\Fixtures\BaseTestClass;
use Pgs\HashIdBundle\Tests\Traits\Fixtures\DecorateTestClass;
use PHPUnit\Framework\TestCase;

class DecoratorTraitTest extends TestCase
{
    protected $decorateClass;

    protected function setUp(): void
    {
        $baseClass = new BaseTestClass();
        $this->decorateClass = new DecorateTestClass($baseClass);
    }

    public function testExistingMethodCall()
    {
        self::assertTrue($this->decorateClass->existingMethod1());
    }

    public function testNonExistingMethodCall()
    {
        $this->expectException(BadMethodCallException::class);
        $this->decorateClass->nonExistingMethod();
    }
}
