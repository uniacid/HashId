<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Integration;

use ReflectionClass;
use Pgs\HashIdBundle\ParametersProcessor\ParametersProcessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Annotation\Hash as HashAnnotation;
use Pgs\HashIdBundle\AnnotationProvider\AttributeProvider;
use Pgs\HashIdBundle\Attribute\Hash as HashAttribute;
use Pgs\HashIdBundle\Decorator\RouterDecorator;
use Pgs\HashIdBundle\ParametersProcessor\Factory\EncodeParametersProcessorFactory;
use Pgs\HashIdBundle\Reflection\ReflectionProvider;
use Pgs\HashIdBundle\Service\CompatibilityLayer;
use Pgs\HashIdBundle\Service\HasherFactory;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RequestContext;

/**
 * Integration test for attribute → annotation fallback flow.
 * 
 * This test verifies the complete flow from router generation through
 * parameter processing, ensuring both attributes and annotations work
 * correctly with proper fallback behavior.
 * 
 * @group integration
 * @covers \Pgs\HashIdBundle\Service\CompatibilityLayer
 * @covers \Pgs\HashIdBundle\AnnotationProvider\AttributeProvider
 * @covers \Pgs\HashIdBundle\Decorator\RouterDecorator
 */
class AttributeAnnotationFallbackTest extends TestCase
{
    private CompatibilityLayer $compatibilityLayer;
    private AttributeProvider $attributeProvider;
    
    protected function setUp(): void
    {
        $this->compatibilityLayer = new CompatibilityLayer();
        $reflectionProvider = new ReflectionProvider();
        $this->attributeProvider = new AttributeProvider(
            $this->compatibilityLayer,
            $reflectionProvider
        );
    }
    
    /**
     * Test attribute-only configuration.
     */
    public function testAttributeOnlyConfiguration(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Attributes require PHP 8.0+');
        }
        
        $controller = new class() {
            #[HashAttribute(['id', 'parentId'])]
            public function show(int $id, int $parentId): void
            {
            }
        };
        
        $reflectionClass = new ReflectionClass($controller);
        $method = $reflectionClass->getMethod('show');
        
        // Test extraction through compatibility layer
        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);
        $this->assertSame(['id', 'parentId'], $parameters);
        
        // Test through attribute provider
        $config = $this->attributeProvider->getFromObject($controller, 'show', HashAttribute::class);
        $this->assertNotNull($config);
        $this->assertSame(['id', 'parentId'], $config->getParameters());
    }
    
    /**
     * Test annotation-only configuration (fallback).
     */
    public function testAnnotationOnlyConfiguration(): void
    {
        $controller = new class() {
            /**
             * @Hash({"id", "userId"})
             */
            public function edit(int $id, int $userId): void
            {
            }
        };
        
        $reflectionClass = new ReflectionClass($controller);
        $method = $reflectionClass->getMethod('edit');
        
        // Test extraction through compatibility layer
        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);
        $this->assertSame(['id', 'userId'], $parameters);
        
        // Test through attribute provider
        $config = $this->attributeProvider->getFromObject($controller, 'edit', HashAnnotation::class);
        $this->assertNotNull($config);
        $this->assertSame(['id', 'userId'], $config->getParameters());
    }
    
    /**
     * Test attribute → annotation fallback when attribute is not present.
     */
    public function testAttributeToAnnotationFallback(): void
    {
        $controller = new class() {
            /**
             * @Hash("orderId")
             */
            public function view(int $orderId): void
            {
            }
        };
        
        $reflectionClass = new ReflectionClass($controller);
        $method = $reflectionClass->getMethod('view');
        
        // Compatibility layer should check for attribute first, then fall back to annotation
        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);
        $this->assertSame(['orderId'], $parameters);
        
        // Verify it's using annotation (not attribute)
        if (PHP_VERSION_ID >= 80000) {
            $attributes = $method->getAttributes(HashAttribute::class);
            $this->assertEmpty($attributes);
        }
    }
    
    /**
     * Test priority when both attribute and annotation are present.
     */
    public function testAttributeTakesPriorityOverAnnotation(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Attributes require PHP 8.0+');
        }
        
        $controller = new class() {
            /**
             * @Hash("annotationParam")
             */
            #[HashAttribute('attributeParam')]
            public function update(int $id): void
            {
            }
        };
        
        $reflectionClass = new ReflectionClass($controller);
        $method = $reflectionClass->getMethod('update');
        
        // Should prefer attribute over annotation
        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);
        $this->assertSame(['attributeParam'], $parameters);
        
        // Verify duplicate configuration is detected
        $this->assertTrue($this->compatibilityLayer->hasDuplicateConfiguration($method));
    }
    
    /**
     * Test complete flow through router decorator.
     */
    public function testCompleteFlowWithRouterDecorator(): void
    {
        // Create mock router
        $router = $this->createMock(Router::class);
        $routeCollection = new RouteCollection();
        
        // Add test route with Hash configuration
        $route = new Route('/user/{id}/post/{postId}');
        $route->setDefault('_controller', 'TestController::show');
        $routeCollection->add('user_post', $route);
        
        $router->method('getRouteCollection')->willReturn($routeCollection);
        $router->method('getContext')->willReturn(new RequestContext());
        
        // Create hasher factory and processor factory
        $hasherFactory = new HasherFactory('test-salt', 10);
        $processorFactory = $this->createMock(EncodeParametersProcessorFactory::class);
        
        // Create mock processor
        $processor = $this->createMock(ParametersProcessorInterface::class);
        $processor->method('needToProcess')->willReturn(true);
        $processor->method('process')->willReturnCallback(function ($params) {
            // Simulate encoding
            if (isset($params['id'])) {
                $params['id'] = 'encoded_' . $params['id'];
            }
            if (isset($params['postId'])) {
                $params['postId'] = 'encoded_' . $params['postId'];
            }
            return $params;
        });
        
        $processorFactory->method('createRouteEncodeParametersProcessor')
            ->willReturn($processor);
        
        // Create router decorator
        $decorator = new RouterDecorator($router, $processorFactory);
        
        // Generate URL with parameters
        $router->expects($this->once())
            ->method('generate')
            ->with('user_post', ['id' => 'encoded_123', 'postId' => 'encoded_456'])
            ->willReturn('/user/encoded_123/post/encoded_456');
        
        $url = $decorator->generate('user_post', ['id' => 123, 'postId' => 456]);
        
        $this->assertSame('/user/encoded_123/post/encoded_456', $url);
    }
    
    /**
     * Test caching behavior in compatibility layer.
     */
    public function testCachingBehavior(): void
    {
        $controller = new class() {
            /**
             * @Hash("id")
             */
            public function cached(int $id): void
            {
            }
        };
        
        $reflectionClass = new ReflectionClass($controller);
        $method = $reflectionClass->getMethod('cached');
        
        // Clear cache first
        CompatibilityLayer::clearCache();
        $this->assertSame(0, CompatibilityLayer::getCacheSize());
        
        // First call should cache the result
        $parameters1 = $this->compatibilityLayer->extractHashConfiguration($method);
        $this->assertSame(['id'], $parameters1);
        $this->assertSame(1, CompatibilityLayer::getCacheSize());
        
        // Second call should use cached result
        $parameters2 = $this->compatibilityLayer->extractHashConfiguration($method);
        $this->assertSame(['id'], $parameters2);
        $this->assertSame(1, CompatibilityLayer::getCacheSize()); // Still 1, not 2
        
        // Clear cache
        CompatibilityLayer::clearCache();
        $this->assertSame(0, CompatibilityLayer::getCacheSize());
    }
    
    /**
     * Test error handling for invalid configurations.
     */
    public function testErrorHandlingForInvalidConfigurations(): void
    {
        $controller = new class() {
            /**
             * @Hash({123, "invalid-param!"})
             */
            public function invalid(): void
            {
            }
        };
        
        $reflectionClass = new ReflectionClass($controller);
        $method = $reflectionClass->getMethod('invalid');
        
        // Should handle invalid parameters gracefully
        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);
        
        // Invalid parameters should be filtered out or null returned
        $this->assertTrue(
            $parameters === null || !in_array('invalid-param!', $parameters, true),
            'Invalid parameters should be filtered out'
        );
    }
    
    /**
     * Test Symfony 6.4+ compatibility.
     */
    public function testSymfony64Compatibility(): void
    {
        // Verify router decorator implements required interfaces
        $router = $this->createMock(Router::class);
        $processorFactory = $this->createMock(EncodeParametersProcessorFactory::class);
        $decorator = new RouterDecorator($router, $processorFactory);
        
        $this->assertInstanceOf(RouterInterface::class, $decorator);
        $this->assertInstanceOf(WarmableInterface::class, $decorator);
        
        // Test warmUp method returns empty array when router doesn't implement WarmableInterface
        $result = $decorator->warmUp('/cache/dir');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    /**
     * Create a mock router for testing.
     */
    private function createMockRouter(): Router
    {
        return $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}