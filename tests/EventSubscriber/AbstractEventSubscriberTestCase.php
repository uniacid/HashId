<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\EventSubscriber;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

abstract class AbstractEventSubscriberTestCase extends TestCase
{
    protected function subscribedEventsList(string $eventSubscriberClass): void
    {
        self::assertTrue(\array_key_exists(KernelEvents::CONTROLLER, $eventSubscriberClass::getSubscribedEvents()));
    }

    /**
     * @return ControllerEvent
     */
    protected function getEventMock(): ControllerEvent
    {
        // ControllerEvent is final in Symfony 6.4/7.0, so we need to create a real instance
        $kernel = $this->createMock(\Symfony\Component\HttpKernel\HttpKernelInterface::class);
        $request = $this->getRequestMock();

        // Create a simple callable controller for testing
        $controller = function() { return null; };

        // Create a real ControllerEvent instance
        $event = new ControllerEvent($kernel, $controller, $request, \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST);

        return $event;
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
