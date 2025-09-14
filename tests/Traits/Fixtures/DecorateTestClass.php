<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Traits\Fixtures;

use Pgs\HashIdBundle\Traits\DecoratorTrait;

class DecorateTestClass
{
    use DecoratorTrait;

    public function __construct($object)
    {
        $this->object = $object;
    }
}
