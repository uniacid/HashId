<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\ParametersProcessor\Factory;

use Pgs\HashIdBundle\ParametersProcessor\Decode;
use Pgs\HashIdBundle\ParametersProcessor\Factory\DecodeParametersProcessorFactory;
use Pgs\HashIdBundle\ParametersProcessor\NoOp;

class DecodeParametersProcessorFactoryTest extends ParametersProcessorFactoryTest
{
    public function testCreateControllerDecodeParametersProcessor(): void
    {
        $decodeParametersProcessor = $this
            ->getParametersProcessorMockProvider()
            ->getParametersProcessorMock(Decode::class);

        $noOpParametersProcessor = $this
            ->getParametersProcessorMockProvider()
            ->getParametersProcessorMock(NoOp::class);

        $parametersProcessorFactory = new DecodeParametersProcessorFactory(
            $this->getControllerAnnotationMockProvider()->getExistingControllerAnnotationProviderMock(),
            $noOpParametersProcessor,
            $decodeParametersProcessor,
        );

        $parametersProcessor = $parametersProcessorFactory
            ->createControllerDecodeParametersProcessor(
                $this->getControllerMockProvider()->getTestControllerMock(),
                'testMethod',
            );
        self::assertInstanceOf(\get_class($decodeParametersProcessor), $parametersProcessor);
    }

    public function testCreateControllerDecodeParametersProcessorForBadController(): void
    {
        $decodeParametersProcessor = $this
            ->getParametersProcessorMockProvider()
            ->getParametersProcessorMock(Decode::class);

        $noOpParametersProcessor = $this
            ->getParametersProcessorMockProvider()
            ->getParametersProcessorMock(NoOp::class);
        $parametersProcessorFactory = new DecodeParametersProcessorFactory(
            $this
                ->getControllerAnnotationMockProvider()
                ->getInvalidControllerExceptionControllerAnnotationProviderMock(),
            $noOpParametersProcessor,
            $decodeParametersProcessor,
        );

        $parametersProcessor = $parametersProcessorFactory->createControllerDecodeParametersProcessor(
            'test_controller_string',
            'testMethod',
        );
        self::assertInstanceOf(\get_class($noOpParametersProcessor), $parametersProcessor);
    }
}
