<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Controller;

use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

trait ControllerMockProvider
{
    abstract protected function getMockBuilder(string $className): MockBuilder;
    
    public function getTestControllerMock(): AbstractController
    {
        return $this->getMockBuilder(AbstractController::class)
            ->getMockForAbstractClass();
    }

    public function getTestControllerObjectMock(): AbstractController
    {
        return $this->getMockBuilder(AbstractController::class)
            ->onlyMethods(['__invoke'])
            ->getMockForAbstractClass();
}
