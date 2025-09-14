<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\DependencyInjection;

use Hashids\Hashids;
use Pgs\HashIdBundle\DependencyInjection\PgsHashIdExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PgsHashIdExtensionTest extends TestCase
{
    public function testExtensionLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new PgsHashIdExtension();
        $extension->load([], $container);
        if (\class_exists(Hashids::class)) {
            self::assertTrue($container->hasParameter('pgs_hash_id.converter.hashids.salt'));
            self::assertTrue($container->hasParameter('pgs_hash_id.converter.hashids.min_hash_length'));
            self::assertTrue($container->hasParameter('pgs_hash_id.converter.hashids.alphabet'));
        }
    }
}
