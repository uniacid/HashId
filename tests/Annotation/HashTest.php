<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Annotation;

use Pgs\HashIdBundle\Annotation\Hash;
use PHPUnit\Framework\TestCase;

class HashTest extends TestCase
{
    public function testGetParameters()
    {
        $parameters = ['id'];
        $hash = new Hash($parameters);
        self::assertSame(['id'], $hash->getParameters());
    }

    public function testPassValueIndexedParameters()
    {
        $parameters = [
            'value' => ['id', 'second'],
        ];
        $hash = new Hash($parameters);
        self::assertSame($parameters['value'], $hash->getParameters());
    }
}
