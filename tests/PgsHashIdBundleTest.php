<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests;

use Pgs\HashIdBundle\DependencyInjection\Compiler\EventSubscriberCompilerPass;
use Pgs\HashIdBundle\DependencyInjection\PgsHashIdExtension;
use Pgs\HashIdBundle\PgsHashIdBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PgsHashIdBundleTest extends TestCase
{
    public function testGetContainerExtension(): void
    {
        $bundle = new PgsHashIdBundle();
        self::assertInstanceOf(PgsHashIdExtension::class, $bundle->getContainerExtension());
    }

    public function testRegisterCompilerPass(): void
    {
        $container = new ContainerBuilder();
        $bundle = new PgsHashIdBundle();
        $bundle->build($container);

        self::assertTrue(\in_array(EventSubscriberCompilerPass::class, $this->getPassesClasses($container), true));
    }

    private function getPassesClasses(ContainerBuilder $container): array
    {
        $passes = $container->getCompilerPassConfig()->getPasses();

        return \array_map(fn ($item) => \get_class($item), $passes);
    }
}
