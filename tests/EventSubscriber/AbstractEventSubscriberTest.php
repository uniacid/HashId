<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\EventSubscriber;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

abstract class AbstractEventSubscriberTest extends TestCase
{
    protected function subscribedEventsList(string $eventSubscriberClass): void
    {
        self::assertTrue(\array_key_exists(KernelEvents::CONTROLLER, $eventSubscriberClass::getSubscribedEvents()));
    }

    /**
     * @return ControllerEvent|MockObject
     */
    protected function getEventMock(): ControllerEvent
    {
        $mock = $this->getMockBuilder(ControllerEvent::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequest'])
            ->getMock();

        $mock->method('getRequest')->willReturn($this->getRequestMock());

        return $mock;
    }

    /**
     * @return Request|MockObject
     */
    protected function getRequestMock(): Request
    {
        $mock = $this->getMockBuilder(Request::class)
            ->getMock();
        $parametersBag = $this->getMockBuilder(ParameterBag::class)->onlyMethods(['all'])->getMock();
        $parametersBag->method('all')->willReturnOnConsecutiveCalls(
            [
                'id' => 'encoded',
                'name' => 'test',
            ],
            [
                'id' => 10,
                'name' => 'test',
            ],
        );
        $mock->attributes = $parametersBag;

        return $mock;
    }
}
