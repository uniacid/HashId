<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\ParametersProcessor\Factory;

use Pgs\HashIdBundle\ParametersProcessor\Encode;
use Pgs\HashIdBundle\ParametersProcessor\Factory\EncodeParametersProcessorFactory;
use Pgs\HashIdBundle\ParametersProcessor\NoOp;

class EncodeParametersProcessorFactoryTest extends ParametersProcessorFactoryTestCase
{
    public function testCreateRouteEncodeParametersProcessor(): void
    {
        $encodeParametersProcessor = $this
            ->getParametersProcessorMockProvider()
            ->getParametersProcessorMock(Encode::class);

        $noOpParametersProcessor = $this
            ->getParametersProcessorMockProvider()
            ->getParametersProcessorMock(NoOp::class);

        $parametersProcessorFactory = new EncodeParametersProcessorFactory(
            $this->getControllerAnnotationMockProvider()->getExistingControllerAnnotationProviderMock(),
            $noOpParametersProcessor,
            $encodeParametersProcessor,
        );

        $parametersProcessor = $parametersProcessorFactory
            ->createRouteEncodeParametersProcessor(
                $this->getRouteMockProvider()->getTestRouteMock(),
            );
        self::assertInstanceOf(\get_class($encodeParametersProcessor), $parametersProcessor);
    }

    public function testCreateRouteNoOpParametersProcessor(): void
    {
        $encodeParametersProcessor = $this
            ->getParametersProcessorMockProvider()
            ->getParametersProcessorMock(Encode::class);

        $noOpParametersProcessor = $this
            ->getParametersProcessorMockProvider()
            ->getParametersProcessorMock(NoOp::class);

        $parametersProcessorFactory = new EncodeParametersProcessorFactory(
            $this->getControllerAnnotationMockProvider()->getNotExistingControllerAnnotationProviderMock(),
            $noOpParametersProcessor,
            $encodeParametersProcessor,
        );

        $parametersProcessor = $parametersProcessorFactory->createRouteEncodeParametersProcessor(
            $this->getRouteMockProvider()->getTestRouteMock(),
        );
        self::assertInstanceOf(\get_class($noOpParametersProcessor), $parametersProcessor);

        $parametersProcessorFactory = new EncodeParametersProcessorFactory(
            $this->getControllerAnnotationMockProvider()->getExceptionThrowControllerAnnotationProviderMock(),
            $noOpParametersProcessor,
            $encodeParametersProcessor,
        );
        $parametersProcessor = $parametersProcessorFactory->createRouteEncodeParametersProcessor(
            $this->getRouteMockProvider()->getInvalidRouteMock(),
        );
        self::assertInstanceOf(\get_class($noOpParametersProcessor), $parametersProcessor);
    }
}
