<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Modernization;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Tests\Fixtures\Php83TestFixtures;

/**
 * Test anonymous readonly classes implementation for PHP 8.3.
 * 
 * @since 4.0.0
 */
class AnonymousReadonlyClassTest extends TestCase
{
    /**
     * Test mock converter with anonymous readonly class.
     */
    public function testMockConverterWithAnonymousReadonlyClass(): void
    {
        if (PHP_VERSION_ID < 80300) {
            $this->markTestSkipped('PHP 8.3+ required for anonymous readonly classes');
        }
        
        $converter = Php83TestFixtures::createMockConverter('test_');
        
        // Test encoding
        $encoded = $converter->encode(123);
        $this->assertEquals('test_123', $encoded);
        
        // Test decoding
        $decoded = $converter->decode('test_456');
        $this->assertEquals(456, $decoded);
        
        // Test non-numeric values
        $this->assertEquals('abc', $converter->encode('abc'));
        $this->assertEquals('xyz', $converter->decode('xyz'));
    }
    
    /**
     * Test mock parameters processor.
     */
    public function testMockParametersProcessor(): void
    {
        if (PHP_VERSION_ID < 80300) {
            $this->markTestSkipped('PHP 8.3+ required for anonymous readonly classes');
        }
        
        $processor = Php83TestFixtures::createMockParametersProcessor([
            'processed' => true,
            'timestamp' => 1234567890,
        ]);
        
        $result = $processor->process(['id' => 123]);
        
        $this->assertEquals([
            'id' => 123,
            'processed' => true,
            'timestamp' => 1234567890,
        ], $result);
    }
    
    /**
     * Test mock request creation.
     */
    public function testMockRequest(): void
    {
        if (PHP_VERSION_ID < 80300) {
            $this->markTestSkipped('PHP 8.3+ required for anonymous readonly classes');
        }
        
        $request = Php83TestFixtures::createMockRequest([
            'id' => 'encoded_123',
            'other' => 'encoded_456',
        ]);
        
        $routeParams = $request->attributes->get('_route_params');
        
        $this->assertEquals('encoded_123', $routeParams['id']);
        $this->assertEquals('encoded_456', $routeParams['other']);
    }
    
    /**
     * Test mock controller with annotations.
     */
    public function testMockController(): void
    {
        if (PHP_VERSION_ID < 80300) {
            $this->markTestSkipped('PHP 8.3+ required for anonymous readonly classes');
        }
        
        $controller = Php83TestFixtures::createMockController([
            'decode' => ['id'],
            'decodeMore' => ['id', 'other'],
        ]);
        
        $this->assertEquals(['id'], $controller->getHashParameters('decode'));
        $this->assertEquals(['id', 'other'], $controller->getHashParameters('decodeMore'));
        $this->assertTrue($controller->hasMethod('decode'));
        $this->assertFalse($controller->hasMethod('nonexistent'));
    }
    
    /**
     * Test mock hashids configuration.
     */
    public function testMockHashidsConfig(): void
    {
        if (PHP_VERSION_ID < 80300) {
            $this->markTestSkipped('PHP 8.3+ required for anonymous readonly classes');
        }
        
        $config = Php83TestFixtures::createMockHashidsConfig(
            'my_salt',
            20,
            'abcdef123456'
        );
        
        $this->assertEquals('my_salt', $config->salt);
        $this->assertEquals(20, $config->minLength);
        $this->assertEquals('abcdef123456', $config->alphabet);
        
        $array = $config->toArray();
        $this->assertEquals([
            'salt' => 'my_salt',
            'min_length' => 20,
            'alphabet' => 'abcdef123456',
        ], $array);
    }
    
    /**
     * Test mock route collection.
     */
    public function testMockRouteCollection(): void
    {
        if (PHP_VERSION_ID < 80300) {
            $this->markTestSkipped('PHP 8.3+ required for anonymous readonly classes');
        }
        
        $routes = Php83TestFixtures::createMockRouteCollection([
            'demo_encode' => ['path' => '/encode/{id}', 'methods' => ['GET']],
            'demo_decode' => ['path' => '/decode/{id}', 'methods' => ['GET', 'POST']],
        ]);
        
        $this->assertTrue($routes->has('demo_encode'));
        $this->assertFalse($routes->has('nonexistent'));
        $this->assertEquals(2, $routes->count());
        
        $route = $routes->get('demo_encode');
        $this->assertEquals('/encode/{id}', $route['path']);
        $this->assertEquals(['GET'], $route['methods']);
    }
    
    /**
     * Test mock controller event.
     */
    public function testMockControllerEvent(): void
    {
        if (PHP_VERSION_ID < 80300) {
            $this->markTestSkipped('PHP 8.3+ required for anonymous readonly classes');
        }
        
        $request = Php83TestFixtures::createMockRequest(['id' => 123]);
        $controller = Php83TestFixtures::createMockController(['test' => ['id']]);
        
        $event = Php83TestFixtures::createMockControllerEvent($request, $controller);
        
        $this->assertSame($request, $event->getRequest());
        $this->assertSame($controller, $event->getController());
        $this->assertTrue($event->hasController());
    }
    
    /**
     * Test that anonymous readonly classes are actually readonly.
     */
    public function testAnonymousClassesAreReadonly(): void
    {
        if (PHP_VERSION_ID < 80300) {
            $this->markTestSkipped('PHP 8.3+ required for anonymous readonly classes');
        }
        
        $converter = Php83TestFixtures::createMockConverter('test_');
        $reflection = new \ReflectionClass($converter);
        
        // Check if class is readonly (PHP 8.3+)
        if (method_exists($reflection, 'isReadOnly')) {
            $this->assertTrue($reflection->isReadOnly(), 'Anonymous class should be readonly');
        }
        
        // Check that properties are readonly
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            if (method_exists($property, 'isReadOnly')) {
                $this->assertTrue($property->isReadOnly(), 
                    "Property {$property->getName()} should be readonly");
            }
        }
    }
    
    /**
     * Test complex fixture composition.
     */
    public function testComplexFixtureComposition(): void
    {
        if (PHP_VERSION_ID < 80300) {
            $this->markTestSkipped('PHP 8.3+ required for anonymous readonly classes');
        }
        
        // Create a complex test scenario using multiple fixtures
        $converter = Php83TestFixtures::createMockConverter('hash_');
        $processor = Php83TestFixtures::createMockParametersProcessor(['converter' => $converter]);
        $request = Php83TestFixtures::createMockRequest(['id' => 'hash_999']);
        $controller = Php83TestFixtures::createMockController(['action' => ['id']]);
        $event = Php83TestFixtures::createMockControllerEvent($request, $controller);
        
        // Test the composition
        $this->assertTrue($event->hasController());
        $params = $event->getRequest()->attributes->get('_route_params');
        $this->assertEquals('hash_999', $params['id']);
        
        // Decode the parameter
        $decoded = $converter->decode($params['id']);
        $this->assertEquals(999, $decoded);
        
        // Process parameters
        $processed = $processor->process(['id' => $decoded]);
        $this->assertArrayHasKey('converter', $processed);
        $this->assertSame($converter, $processed['converter']);
    }
}