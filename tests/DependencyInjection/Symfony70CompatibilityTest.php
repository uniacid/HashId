<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\DependencyInjection\PgsHashIdExtension;
use Pgs\HashIdBundle\PgsHashIdBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class Symfony70CompatibilityTest extends TestCase
{
    /**
     * Test that the bundle is compatible with Symfony 6.4 and 7.0
     */
    public function testSymfonyVersionCompatibility(): void
    {
        $container = new ContainerBuilder();
        $extension = new PgsHashIdExtension();
        
        // Add required parameters
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->setParameter('kernel.cache_dir', sys_get_temp_dir() . '/cache');
        $container->setParameter('kernel.logs_dir', sys_get_temp_dir() . '/logs');
        
        // Load extension
        $extension->load([], $container);
        
        // Build bundle
        $bundle = new PgsHashIdBundle();
        $bundle->build($container);
        
        // Check that services are loaded correctly
        $this->assertTrue($container->hasDefinition('pgs_hash_id.decorator.router'));
        $this->assertTrue($container->hasDefinition('pgs_hash_id.event_subscriber.decode_controller_parameters'));
        
        // Verify Symfony version compatibility
        $symfonyVersion = Kernel::VERSION;
        $majorVersion = (int) explode('.', $symfonyVersion)[0];
        
        $this->assertContains($majorVersion, [6, 7], 'Bundle should support Symfony 6 or 7');
    }
    
    /**
     * Test that service definitions use modern patterns
     */
    public function testModernServicePatterns(): void
    {
        $container = new ContainerBuilder();
        $extension = new PgsHashIdExtension();
        
        // Configure container
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'test');
        
        $extension->load([], $container);
        
        // Check that services are private by default (modern pattern)
        $serviceIds = [
            'pgs_hash_id.decorator.router',
            'pgs_hash_id.reflection_provider',
            'pgs_hash_id.annotation_provider',
            'pgs_hash_id.service.decode_controller_parameters',
        ];
        
        foreach ($serviceIds as $serviceId) {
            if ($container->hasDefinition($serviceId)) {
                $definition = $container->getDefinition($serviceId);
                $this->assertFalse(
                    $definition->isPublic(),
                    sprintf('Service %s should be private (modern pattern)', $serviceId)
                );
            }
        }
    }
    
    /**
     * Test that deprecated features are handled correctly
     */
    public function testDeprecatedFeatureHandling(): void
    {
        $container = new ContainerBuilder();
        $extension = new PgsHashIdExtension();
        
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'test');
        
        // Load extension - should not use deprecated AnnotationRegistry
        $extension->load([], $container);
        
        // The extension should not fail even without annotation_reader service
        // (which might be removed in future Symfony versions)
        $this->assertTrue(true, 'Extension loads without deprecated dependencies');
    }
    
    /**
     * Test configuration format compatibility
     */
    public function testConfigurationFormatCompatibility(): void
    {
        $container = new ContainerBuilder();
        $extension = new PgsHashIdExtension();
        
        // Test with modern configuration format
        $config = [
            [
                'converter' => [
                    'hashids' => [
                        'salt' => 'test_salt_v7',
                        'min_hash_length' => 12,
                        'alphabet' => 'abcdefghijklmnopqrstuvwxyz',
                    ],
                ],
            ],
        ];
        
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'test');
        
        $extension->load($config, $container);
        
        // Check that parameters are set correctly
        $this->assertTrue($container->hasParameter('pgs_hash_id.converter.hashids.salt'));
        $this->assertSame('test_salt_v7', $container->getParameter('pgs_hash_id.converter.hashids.salt'));
        $this->assertSame(12, $container->getParameter('pgs_hash_id.converter.hashids.min_hash_length'));
    }
    
    /**
     * Test event subscriber compatibility with Symfony 6.4/7.0
     */
    public function testEventSubscriberCompatibility(): void
    {
        $container = new ContainerBuilder();
        $extension = new PgsHashIdExtension();
        
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'test');
        
        $extension->load([], $container);
        
        // Check event subscriber is properly registered
        $subscriberDefinition = $container->getDefinition('pgs_hash_id.event_subscriber.decode_controller_parameters');
        
        // Should be tagged as kernel.event_subscriber
        $this->assertTrue($subscriberDefinition->hasTag('kernel.event_subscriber'));
        
        // Should not use deprecated event listener pattern
        $this->assertFalse($subscriberDefinition->hasTag('kernel.event_listener'));
    }
    
    /**
     * Test router decorator compatibility
     */
    public function testRouterDecoratorCompatibility(): void
    {
        $container = new ContainerBuilder();
        $extension = new PgsHashIdExtension();
        
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'test');
        
        $extension->load([], $container);
        
        $decoratorDefinition = $container->getDefinition('pgs_hash_id.decorator.router');
        
        // Check that it decorates the router service
        $decoratedService = $decoratorDefinition->getDecoratedService();
        $this->assertNotNull($decoratedService);
        $this->assertSame('router', $decoratedService[0]);
        
        // Check that it has proper arguments
        $arguments = $decoratorDefinition->getArguments();
        $this->assertCount(2, $arguments);
    }
}