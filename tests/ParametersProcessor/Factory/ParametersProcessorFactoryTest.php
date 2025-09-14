<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\ParametersProcessor\Factory;

use Pgs\HashIdBundle\Tests\AnnotationProvider\ControllerAnnotationProviderMockProvider;
use Pgs\HashIdBundle\Tests\Controller\ControllerMockProvider;
use Pgs\HashIdBundle\Tests\ParametersProcessor\ParametersProcessorMockProvider;
use Pgs\HashIdBundle\Tests\Route\RouteMockProvider;
use PHPUnit\Framework\TestCase;

abstract class ParametersProcessorFactoryTest extends TestCase
{
    use ControllerMockProvider;
    use ControllerAnnotationProviderMockProvider;
    use RouteMockProvider;
    use ParametersProcessorMockProvider;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function getControllerMockProvider(): self
    {
        return $this->controllerMockProvider;
    }

    public function getControllerAnnotationMockProvider(): ControllerAnnotationProviderMockProvider
    {
        return $this->controllerAnnotationMockProvider;
    }

    public function getRouteMockProvider(): RouteMockProvider
    {
        return $this->routeMockProvider;
    }

    public function getParametersProcessorMockProvider(): ParametersProcessorMockProvider
    {
        return $this->parametersProcessorMockProvider;
    }
}
