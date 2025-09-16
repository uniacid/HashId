<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Service;

use Pgs\HashIdBundle\ParametersProcessor\Decode;
use Pgs\HashIdBundle\ParametersProcessor\Factory\DecodeParametersProcessorFactory;
use Pgs\HashIdBundle\Service\DecodeControllerParameters;
use Pgs\HashIdBundle\Tests\Controller\ControllerMockProvider;
use Pgs\HashIdBundle\Tests\ParametersProcessor\ParametersProcessorMockProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\EventListener\ParamConverterListener;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class DecodeControllerParametersTest extends TestCase
{
    use ControllerMockProvider;
    use ParametersProcessorMockProvider;

    protected function setUp(): void
    {
        // No need to instantiate traits
    }

    public function getControllerMockProvider(): self
    {
        return $this;
    }

    public function getParametersProcessorMockProvider(): self
    {
        return $this;
    }

    /**
     * @dataProvider decodeControllerParametersDataProvider
     *
     * @param mixed $controllerMarker
     */
    public function testDecodeControllerParameters($controllerMarker): void
    {
        // Create a callable controller (array format for Symfony)
        $controllerInstance = $this->getControllerMockProvider()->getTestControllerMock();
        $controller = [$controllerInstance, 'demo'];

        $decodeParametersProcessorFactory = $this->getDecodeParametersProcessorFactoryMock();
        $decodeControllerParameters = new DecodeControllerParameters($decodeParametersProcessorFactory);
        $event = $this->getEventMock(
            [
                [
                    'id' => 'encoded',
                    'name' => 'test',
                ],
                [
                    'id' => 10,
                    'name' => 'test',
                ],
            ],
            $controller,
        );
        $decodeControllerParameters->decodeControllerParameters($event);
        self::assertSame(10, $event->getRequest()->attributes->all()['id']);
    }

    public static function decodeControllerParametersDataProvider()
    {
        // Data providers are now static in PHPUnit 10, so we can't use $this
        // Return a marker that the test method will use to create the actual mock
        return [
            ['controller_mock_marker'],
        ];
    }

    /**
     * @dataProvider decodeControllerParametersDataProvider
     */
    public function testDecodeControllerParametersWithParamConverter($controllerMarker): void
    {
        // Create a callable controller (array format for Symfony)
        $controllerInstance = $this->getControllerMockProvider()->getTestControllerMock();
        $controller = [$controllerInstance, 'demo'];

        $decodeParametersProcessorFactory = $this->getDecodeParametersProcessorFactoryMock();
        $decodeControllerParameters = new DecodeControllerParameters($decodeParametersProcessorFactory);
        $decodeControllerParameters->setParamConverterListener($this->getDoctrineParamConverterListenerMock());
        $event = $this->getEventMock(
            [
                [
                    'id' => 'encoded',
                    'name' => 'test',
                ],
                [
                    'id' => 10,
                    'name' => 'test',
                ],
            ],
            $controller,
        );
        $decodeControllerParameters->decodeControllerParameters($event);
        self::assertSame(10, $event->getRequest()->attributes->all()['id']);
    }

    /**
     * @return DecodeParametersProcessorFactory|MockObject
     */
    protected function getDecodeParametersProcessorFactoryMock(): DecodeParametersProcessorFactory
    {
        $mock = $this->getMockBuilder(DecodeParametersProcessorFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createControllerDecodeParametersProcessor'])
            ->getMock();

        $mock->method('createControllerDecodeParametersProcessor')
            ->willReturn($this->getDecodeParametersProcessorMock());

        return $mock;
    }

    protected function getDecodeParametersProcessorMock(): Decode
    {
        $mock = $this->getParametersProcessorMockProvider()->getParametersProcessorMock(Decode::class);
        $mock->method('needToProcess')->willReturn(true);

        return $mock;
    }

    /**
     * @return ControllerEvent|MockObject
     */
    protected function getEventMock(array $requestConsecutiveCalls, $controller): ControllerEvent
    {
        // ControllerEvent is final in Symfony 6.4/7.0, so we need to create a real instance
        $kernel = $this->createMock(\Symfony\Component\HttpKernel\HttpKernelInterface::class);
        $request = $this->getRequestMock($requestConsecutiveCalls);

        // Create a real ControllerEvent instance
        $event = new ControllerEvent($kernel, $controller, $request, \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST);

        return $event;
    }

    /**
     * @return Request|MockObject
     */
    protected function getRequestMock(array $consecutiveCalls): Request
    {
        $mock = $this->getMockBuilder(Request::class)
            ->getMock();
        $parametersBag = $this->getMockBuilder(ParameterBag::class)->onlyMethods(['all'])->getMock();
        $parametersBag->method('all')->willReturnOnConsecutiveCalls(...$consecutiveCalls);
        $mock->attributes = $parametersBag;

        return $mock;
    }

    /**
     * @return ParamConverterListener|MockObject
     */
    protected function getDoctrineParamConverterListenerMock(): ParamConverterListener
    {
        $mock = $this->getMockBuilder(ParamConverterListener::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['onKernelController'])
            ->getMock();

        return $mock;
    }
}
