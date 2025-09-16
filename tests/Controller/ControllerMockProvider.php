<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Controller;

use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

trait ControllerMockProvider
{
    abstract protected function getMockBuilder(string $className): MockBuilder;
    
    public function getTestControllerMock(): AbstractController
    {
        $mock = $this->getMockBuilder(AbstractController::class)
            ->addMethods(['demo'])  // Add the demo method for testing
            ->getMockForAbstractClass();

        // Make the demo method callable
        $mock->method('demo')->willReturn(null);

        return $mock;
    }

    public function getTestControllerObjectMock(): AbstractController
    {
        return $this->getMockBuilder(AbstractController::class)
            ->onlyMethods(['__invoke'])
            ->getMockForAbstractClass();
    }
}
