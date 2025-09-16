<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\ParametersProcessor;

trait ParametersProcessorMockProvider
{
    public function getParametersProcessorMock($class)
    {
        $mock = $this
            ->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setParametersToProcess', 'process', 'needToProcess'])
            ->getMock();

        $mock->method('setParametersToProcess')->willReturnSelf();

        return $mock;
    }
}
