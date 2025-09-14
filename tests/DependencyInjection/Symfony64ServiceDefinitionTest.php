<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Decorator\RouterDecorator;
use Pgs\HashIdBundle\DependencyInjection\PgsHashIdExtension;
use Pgs\HashIdBundle\EventSubscriber\DecodeControllerParametersSubscriber;
use Pgs\HashIdBundle\ParametersProcessor\Decode;
use Pgs\HashIdBundle\ParametersProcessor\Encode;
use Pgs\HashIdBundle\ParametersProcessor\NoOp;
use Pgs\HashIdBundle\Service\DecodeControllerParameters;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Symfony64ServiceDefinitionTest extends TestCase
{
    private ContainerBuilder $container;
    private PgsHashIdExtension $extension;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->extension = new PgsHashIdExtension();
        
        // Add required parameters for testing
        $this->container->setParameter('kernel.debug', false);
        $this->container->setParameter('kernel.environment', 'test');
    }

    public function testServiceDefinitionsAreLoadedCorrectly(): void
    {
        $this->extension->load([], $this->container);

        // Check core services are defined
        $this->assertTrue($this->container->hasDefinition('pgs_hash_id.decorator.router'));
        $this->assertTrue($this->container->hasDefinition('pgs_hash_id.reflection_provider'));
        $this->assertTrue($this->container->hasDefinition('pgs_hash_id.annotation_provider'));
        $this->assertTrue($this->container->hasDefinition('pgs_hash_id.parameters_processor.encode'));
        $this->assertTrue($this->container->hasDefinition('pgs_hash_id.parameters_processor.decode'));
        $this->assertTrue($this->container->hasDefinition('pgs_hash_id.parameters_processor.no_op'));
        $this->assertTrue($this->container->hasDefinition('pgs_hash_id.service.decode_controller_parameters'));
        $this->assertTrue($this->container->hasDefinition('pgs_hash_id.event_subscriber.decode_controller_parameters'));
    }

    public function testRouterDecoratorConfiguration(): void
    {
        $this->extension->load([], $this->container);

        $definition = $this->container->getDefinition('pgs_hash_id.decorator.router');
        
        // Verify it's properly configured as a decorator
        $this->assertSame(RouterDecorator::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('router', $definition->getDecoratedService()[0] ?? null);
        
        // Check arguments
        $arguments = $definition->getArguments();
        $this->assertCount(2, $arguments);
    }

    public function testEventSubscriberConfiguration(): void
    {
        $this->extension->load([], $this->container);

        $definition = $this->container->getDefinition('pgs_hash_id.event_subscriber.decode_controller_parameters');
        
        // Verify class
        $this->assertSame(DecodeControllerParametersSubscriber::class, $definition->getClass());
        
        // Check if properly tagged
        $this->assertTrue($definition->hasTag('kernel.event_subscriber'));
        
        // Verify arguments
        $arguments = $definition->getArguments();
        $this->assertCount(1, $arguments);
    }

    public function testAbstractServiceDefinitions(): void
    {
        $this->extension->load([], $this->container);

        // Check abstract services
        $abstractProcessor = $this->container->getDefinition('pgs_hash_id.parameters_processor.abstract');
        $this->assertTrue($abstractProcessor->isAbstract());
        $this->assertFalse($abstractProcessor->isPublic());

        $abstractFactory = $this->container->getDefinition('pgs_hash_id.parameters_processor.factory.abstract');
        $this->assertTrue($abstractFactory->isAbstract());
        $this->assertFalse($abstractFactory->isPublic());
    }

    public function testServiceInheritance(): void
    {
        $this->extension->load([], $this->container);

        // Test encode processor inherits from abstract
        $encodeProcessor = $this->container->getDefinition('pgs_hash_id.parameters_processor.encode');
        $this->assertSame('pgs_hash_id.parameters_processor.abstract', $encodeProcessor->getParent());
        $this->assertSame(Encode::class, $encodeProcessor->getClass());

        // Test decode processor inherits from abstract
        $decodeProcessor = $this->container->getDefinition('pgs_hash_id.parameters_processor.decode');
        $this->assertSame('pgs_hash_id.parameters_processor.abstract', $decodeProcessor->getParent());
        $this->assertSame(Decode::class, $decodeProcessor->getClass());
    }

    public function testFactoryServiceConfiguration(): void
    {
        $this->extension->load([], $this->container);

        // Test encode factory
        $encodeFactory = $this->container->getDefinition('pgs_hash_id.parameters_processor.factory.encode');
        $this->assertSame('pgs_hash_id.parameters_processor.factory.abstract', $encodeFactory->getParent());
        
        // Test decode factory
        $decodeFactory = $this->container->getDefinition('pgs_hash_id.parameters_processor.factory.decode');
        $this->assertSame('pgs_hash_id.parameters_processor.factory.abstract', $decodeFactory->getParent());
    }

    public function testParameterConfiguration(): void
    {
        $config = [
            [
                'converter' => [
                    'hashids' => [
                        'salt' => 'test_salt',
                        'min_hash_length' => 10,
                        'alphabet' => 'abcdefghijklmnopqrstuvwxyz',
                    ],
                ],
            ],
        ];

        $this->extension->load($config, $this->container);

        // Check parameters are set correctly
        $this->assertTrue($this->container->hasParameter('pgs_hash_id.converter.hashids.salt'));
        $this->assertSame('test_salt', $this->container->getParameter('pgs_hash_id.converter.hashids.salt'));
        
        $this->assertTrue($this->container->hasParameter('pgs_hash_id.converter.hashids.min_hash_length'));
        $this->assertSame(10, $this->container->getParameter('pgs_hash_id.converter.hashids.min_hash_length'));
        
        $this->assertTrue($this->container->hasParameter('pgs_hash_id.converter.hashids.alphabet'));
        $this->assertSame('abcdefghijklmnopqrstuvwxyz', $this->container->getParameter('pgs_hash_id.converter.hashids.alphabet'));
    }

    public function testServiceVisibility(): void
    {
        $this->extension->load([], $this->container);

        $privateServices = [
            'pgs_hash_id.decorator.router',
            'pgs_hash_id.reflection_provider',
            'pgs_hash_id.annotation_provider',
            'pgs_hash_id.parameters_processor.abstract',
            'pgs_hash_id.parameters_processor.encode',
            'pgs_hash_id.parameters_processor.decode',
            'pgs_hash_id.parameters_processor.no_op',
            'pgs_hash_id.parameters_processor.factory.abstract',
            'pgs_hash_id.parameters_processor.factory.encode',
            'pgs_hash_id.parameters_processor.factory.decode',
            'pgs_hash_id.service.decode_controller_parameters',
        ];

        foreach ($privateServices as $serviceId) {
            $definition = $this->container->getDefinition($serviceId);
            $this->assertFalse($definition->isPublic(), sprintf('Service %s should be private', $serviceId));
        }
    }

    public function testModernDependencyInjectionPatterns(): void
    {
        $this->extension->load([], $this->container);

        // Verify services use constructor injection (no setter injection)
        $services = [
            'pgs_hash_id.decorator.router',
            'pgs_hash_id.annotation_provider',
            'pgs_hash_id.service.decode_controller_parameters',
            'pgs_hash_id.event_subscriber.decode_controller_parameters',
        ];

        foreach ($services as $serviceId) {
            $definition = $this->container->getDefinition($serviceId);
            $this->assertEmpty($definition->getMethodCalls(), sprintf('Service %s should use constructor injection only', $serviceId));
        }
    }

    public function testSymfony64Compatibility(): void
    {
        // Test that extension doesn't use deprecated Symfony features
        $this->extension->load([], $this->container);

        // Services should not use deprecated factory syntax
        $services = $this->container->getDefinitions();
        foreach ($services as $id => $definition) {
            if (str_starts_with($id, 'pgs_hash_id.')) {
                // Check that factory is either null or uses modern array syntax
                $factory = $definition->getFactory();
                if ($factory !== null) {
                    $this->assertIsArray($factory, sprintf('Service %s should use modern factory syntax', $id));
                }
            }
        }
    }
}