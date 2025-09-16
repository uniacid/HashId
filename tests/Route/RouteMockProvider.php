<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Route;

use Symfony\Component\Routing\Route;

trait RouteMockProvider
{
    public function getTestRouteMock()
    {
        $mock = $this->getMockBuilder(Route::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDefault'])
            ->getMock();
        $mock->method('getDefault')->with('_controller')->willReturn('TestController::testMethod');

        return $mock;
    }

    public function getInvalidRouteMock()
    {
        $mock = $this->getMockBuilder(Route::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDefault'])
            ->getMock();
        $mock->method('getDefault')->with('_controller')->willReturn('bad_controller_string');

        return $mock;
    }
}
