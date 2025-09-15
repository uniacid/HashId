<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\AnnotationProvider;

use Pgs\HashIdBundle\Annotation\Hash;
use Pgs\HashIdBundle\AnnotationProvider\AnnotationProvider;
use Pgs\HashIdBundle\Exception\InvalidControllerException;
use Pgs\HashIdBundle\Tests\Controller\ControllerMockProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockBuilder;
trait ControllerAnnotationProviderMockProvider
{
    use ControllerMockProvider;

    public function getExistingControllerAnnotationProviderMock(): AnnotationProvider
    {
        $mock = $this->getControllerAnnotationProviderMock();
        $mock->method('getFromString')->with('TestController::testMethod', Hash::class)->willReturn(new Hash([]));
        $mock->method('getFromObject')->with(
            $this->getTestControllerMock(),
            'testMethod',
            Hash::class,
        )->willReturn(new Hash([]));

        return $mock;
    }

    public function getInvalidControllerExceptionControllerAnnotationProviderMock(): AnnotationProvider
    {
        $mock = $this->getControllerAnnotationProviderMock();
        $mock->method('getFromObject')
            ->with('test_controller_string', 'testMethod', Hash::class)
            ->willThrowException(new InvalidControllerException());

        return $mock;
    }

    public function getNotExistingControllerAnnotationProviderMock(): AnnotationProvider
    {
        $mock = $this->getControllerAnnotationProviderMock();
        $mock->method('getFromString')
            ->with('TestController::testMethod', Hash::class)->willReturn(null);

        return $mock;
    }

    public function getExceptionThrowControllerAnnotationProviderMock(): AnnotationProvider
    {
        $mock = $this->getControllerAnnotationProviderMock();
        $mock->method('getFromString')
            ->with('bad_controller_string', Hash::class)
            ->willThrowException(new InvalidControllerException());

        return $mock;
    }

    private function getControllerAnnotationProviderMock()
    {
        return $this->getMockBuilder(AnnotationProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFromString', 'getFromObject'])
            ->getMock();
    }
}
