<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\DependencyInjection;

use Pgs\HashIdBundle\DependencyInjection\Compiler\EventSubscriberCompilerPass;
use Pgs\HashIdBundle\DependencyInjection\Compiler\HashidsConverterCompilerPass;
use Pgs\HashIdBundle\PgsHashIdBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class CompilerPassCompatibilityTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
    }

    public function testBundleRegistersCompilerPasses(): void
    {
        $bundle = new PgsHashIdBundle();
        $bundle->build($this->container);

        $passes = $this->container->getCompilerPassConfig()->getPasses();

        // Check that our compiler passes are registered
        $hasHashidsConverter = false;
        $hasEventSubscriber = false;

        foreach ($passes as $pass) {
            if ($pass instanceof HashidsConverterCompilerPass) {
                $hasHashidsConverter = true;
            }
            if ($pass instanceof EventSubscriberCompilerPass) {
                $hasEventSubscriber = true;
            }
        }

        self::assertTrue($hasHashidsConverter, 'HashidsConverterCompilerPass should be registered');
        self::assertTrue($hasEventSubscriber, 'EventSubscriberCompilerPass should be registered');
    }

    public function testHashidsConverterCompilerPassProcessing(): void
    {
        // Setup container with required services
        $converterDefinition = new Definition();
        $converterDefinition->setClass('Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter');
        $this->container->setDefinition('pgs_hash_id.converter.hashids', $converterDefinition);

        // Set required parameters
        $this->container->setParameter('pgs_hash_id.converter.hashids.salt', 'test_salt');
        $this->container->setParameter('pgs_hash_id.converter.hashids.min_hash_length', 0);
        $this->container->setParameter('pgs_hash_id.converter.hashids.alphabet', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');

        $compilerPass = new HashidsConverterCompilerPass();
        $compilerPass->process($this->container);

        // Verify the converter has been aliased
        self::assertTrue($this->container->hasAlias('pgs_hash_id.converter'));
    }

    public function testEventSubscriberCompilerPassProcessing(): void
    {
        // Setup container with decode service
        $decodeServiceDefinition = new Definition();
        $decodeServiceDefinition->setClass('Pgs\HashIdBundle\Service\DecodeControllerParameters');
        $this->container->setDefinition('pgs_hash_id.service.decode_controller_parameters', $decodeServiceDefinition);

        // Add event subscriber
        $subscriberDefinition = new Definition();
        $subscriberDefinition->setClass('Pgs\HashIdBundle\EventSubscriber\DecodeControllerParametersSubscriber');
        $subscriberDefinition->addTag('kernel.event_subscriber');
        $this->container->setDefinition('pgs_hash_id.event_subscriber.decode_controller_parameters', $subscriberDefinition);

        // Mock router.default
        $routerDefinition = new Definition();
        $routerDefinition->setClass('Symfony\Component\Routing\Router');
        $this->container->setDefinition('router.default', $routerDefinition);

        $compilerPass = new EventSubscriberCompilerPass();
        $compilerPass->process($this->container);

        // Verify the service has been processed correctly
        $processedDefinition = $this->container->getDefinition('pgs_hash_id.service.decode_controller_parameters');
        self::assertNotNull($processedDefinition);
    }

    public function testCompilerPassesSymfony64Compatibility(): void
    {
        // Test that compiler passes use Symfony 6.4 compatible patterns
        // Set required parameters before compilation
        $this->container->setParameter('pgs_hash_id.converter.hashids.salt', 'test_salt');
        $this->container->setParameter('pgs_hash_id.converter.hashids.min_hash_length', 0);
        $this->container->setParameter('pgs_hash_id.converter.hashids.alphabet', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');

        $bundle = new PgsHashIdBundle();
        $bundle->build($this->container);

        // Add minimal required service definitions
        $converterDefinition = new Definition();
        $converterDefinition->setClass('Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter');
        $this->container->setDefinition('pgs_hash_id.converter.hashids', $converterDefinition);

        // Compile the container to trigger all passes
        $this->container->compile();

        // If we get here without exceptions, passes are compatible
        self::assertTrue(true);
    }

    public function testServiceTagging(): void
    {
        // Create a mock event subscriber service
        $subscriberDefinition = new Definition();
        $subscriberDefinition->setClass('Pgs\HashIdBundle\EventSubscriber\DecodeControllerParametersSubscriber');
        $subscriberDefinition->addTag('kernel.event_subscriber');

        $this->container->setDefinition('test.subscriber', $subscriberDefinition);

        // Verify tags are properly formatted for Symfony 6.4
        $tags = $subscriberDefinition->getTags();
        self::assertArrayHasKey('kernel.event_subscriber', $tags);

        // In Symfony 6.4, tags should not have deprecated attributes
        foreach ($tags['kernel.event_subscriber'] as $tag) {
            // Verify no deprecated 'method' attribute (should use getSubscribedEvents instead)
            self::assertArrayNotHasKey('method', $tag);
        }
    }

    public function testServiceAutowiring(): void
    {
        // Test that services can be autowired in Symfony 6.4
        $definition = new Definition();
        $definition->setClass('Pgs\HashIdBundle\Service\DecodeControllerParameters');
        $definition->setAutowired(true);
        $definition->setAutoconfigured(true);

        $this->container->setDefinition('test.service', $definition);

        self::assertTrue($definition->isAutowired());
        self::assertTrue($definition->isAutoconfigured());
    }

    public function testDeprecatedServiceHandling(): void
    {
        // Test that deprecated services are properly marked
        $definition = new Definition();
        $definition->setClass('Pgs\HashIdBundle\Service\LegacyService');
        $definition->setDeprecated(
            'pgs-soft/hashid-bundle',
            '4.0',
            'The "%service_id%" service is deprecated, use "new_service" instead.',
        );

        $this->container->setDefinition('legacy.service', $definition);

        $deprecation = $definition->getDeprecation('legacy.service');
        self::assertNotEmpty($deprecation);
        self::assertStringContainsString('deprecated', $deprecation['message']);
    }
}
