<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\EventSubscriber;

use Pgs\HashIdBundle\EventSubscriber\DecodeControllerParametersSubscriber;
use Pgs\HashIdBundle\Service\DecodeControllerParameters;

class DecodeControllerParametersSubscriberTest extends AbstractEventSubscriberTest
{
    public function testSubscribedEvents(): void
    {
        $this->subscribedEventsList(DecodeControllerParametersSubscriber::class);
    }

    public function testOnKernelController(): void
    {
        $subscriber = new DecodeControllerParametersSubscriber($this->getDecodeControllerParametersMock());
        $event = $this->getEventMock();
        $encodedParameters = $event->getRequest()->attributes->all();
        $subscriber->onKernelController($event);
        self::assertNotSame($encodedParameters, $event->getRequest()->attributes->all());
    }

    protected function getDecodeControllerParametersMock(): DecodeControllerParameters
    {
        return $this->createMock(DecodeControllerParameters::class);
    }
}
