<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Performance;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Service\HasherFactory;
use Pgs\HashIdBundle\Service\CompatibilityLayer;
use Pgs\HashIdBundle\AnnotationProvider\AttributeProvider;
use Pgs\HashIdBundle\Reflection\ReflectionProvider;

/**
 * Performance benchmark tests for critical operations.
 * 
 * @group performance
 * @covers \Pgs\HashIdBundle\Service\HasherFactory
 * @covers \Pgs\HashIdBundle\Service\CompatibilityLayer
 */
class BenchmarkTest extends TestCase
{
    /**
     * Benchmark reflection caching in HasherFactory.
     * Ensures cached operations are significantly faster than uncached.
     */
    public function testReflectionCachingPerformance(): void
    {
        $factory = new HasherFactory();
        
        // Clear cache to ensure clean state
        HasherFactory::clearCache();
        
        // Benchmark first call (uncached)
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            HasherFactory::clearCache(); // Force uncached for each iteration
            $factory->getAvailableTypes();
        }
        $uncachedTime = microtime(true) - $startTime;
        
        // Benchmark cached calls
        HasherFactory::clearCache(); // Clear once
        $factory->getAvailableTypes(); // Warm cache
        
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $factory->getAvailableTypes();
        }
        $cachedTime = microtime(true) - $startTime;
        
        // Assert cached is at least 10x faster
        $this->assertLessThan(
            $uncachedTime / 10,
            $cachedTime,
            sprintf(
                'Cached reflection should be at least 10x faster. Uncached: %.4fs, Cached: %.4fs',
                $uncachedTime,
                $cachedTime
            )
        );
        
        // Log performance metrics for CI
        if (getenv('CI')) {
            echo sprintf(
                "\nReflection Caching Performance:\n" .
                "- Uncached (1000 calls): %.4fs\n" .
                "- Cached (1000 calls): %.4fs\n" .
                "- Improvement: %.2fx faster\n",
                $uncachedTime,
                $cachedTime,
                $uncachedTime / $cachedTime
            );
        }
    }
    
    /**
     * Benchmark string operations optimization in CompatibilityLayer.
     */
    public function testStringOperationsPerformance(): void
    {
        $compatibilityLayer = new CompatibilityLayer();
        
        // Create mock method with array annotation
        $method = $this->createMockMethod();
        $docblock = '/** @Hash({"param1", "param2", "param3", "param4", "param5"}) */';
        $method->method('getDocComment')->willReturn($docblock);
        $method->method('getDeclaringClass')->willReturn($this->createMockReflectionClass());
        $method->method('getName')->willReturn('testMethod');
        
        // Benchmark parameter extraction
        $iterations = 10000;
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $compatibilityLayer->extractHashConfiguration($method);
        }
        
        $totalTime = microtime(true) - $startTime;
        $avgTime = $totalTime / $iterations;
        
        // Should process in under 0.1ms per operation
        $this->assertLessThan(
            0.0001,
            $avgTime,
            sprintf('Average processing time %.6fs exceeds 0.1ms threshold', $avgTime)
        );
        
        if (getenv('CI')) {
            echo sprintf(
                "\nString Operations Performance:\n" .
                "- Total time (%d iterations): %.4fs\n" .
                "- Average per operation: %.6fs\n",
                $iterations,
                $totalTime,
                $avgTime
            );
        }
    }
    
    /**
     * Benchmark regex performance with safe patterns.
     */
    public function testRegexPerformanceWithSafePatterns(): void
    {
        $compatibilityLayer = new CompatibilityLayer();
        
        // Test various input sizes
        $testCases = [
            'small' => 100,
            'medium' => 1000,
            'large' => 5000,
        ];
        
        foreach ($testCases as $size => $length) {
            $method = $this->createMockMethod();
            
            // Create docblock with padding
            $padding = str_repeat(' ', $length);
            $docblock = "/**$padding\n * @Hash(\"testParam\")\n */";
            
            $method->method('getDocComment')->willReturn($docblock);
            $method->method('getDeclaringClass')->willReturn($this->createMockReflectionClass());
            $method->method('getName')->willReturn('testMethod');
            
            $startTime = microtime(true);
            for ($i = 0; $i < 1000; $i++) {
                $compatibilityLayer->extractHashConfiguration($method);
            }
            $elapsed = microtime(true) - $startTime;
            
            // Should complete quickly regardless of input size
            $this->assertLessThan(
                0.5,
                $elapsed,
                sprintf('Processing %s input took too long: %.4fs', $size, $elapsed)
            );
            
            if (getenv('CI')) {
                echo sprintf(
                    "- %s input (%d chars, 1000 iterations): %.4fs\n",
                    ucfirst($size),
                    $length,
                    $elapsed
                );
            }
        }
    }
    
    /**
     * Benchmark hasher instance creation and reuse.
     */
    public function testHasherInstanceReusePerformance(): void
    {
        $factory = new HasherFactory('test-salt', 10, 'abcdefghijklmnopqrstuvwxyz');
        
        // Benchmark creating new instances each time
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $hasher = $factory->createConverter('default', [
                'salt' => 'test-salt-' . $i,
                'min_length' => 10,
                'alphabet' => 'abcdefghijklmnopqrstuvwxyz',
            ]);
            $hasher->encode(12345);
        }
        $newInstanceTime = microtime(true) - $startTime;
        
        // Benchmark reusing same configuration
        $hasher = $factory->createConverter('default', [
            'salt' => 'test-salt',
            'min_length' => 10,
            'alphabet' => 'abcdefghijklmnopqrstuvwxyz',
        ]);
        
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $hasher->encode(12345);
        }
        $reuseTime = microtime(true) - $startTime;
        
        // Reusing should be significantly faster
        $this->assertLessThan(
            $newInstanceTime / 2,
            $reuseTime,
            'Reusing hasher instances should be at least 2x faster'
        );
        
        if (getenv('CI')) {
            echo sprintf(
                "\nHasher Instance Performance:\n" .
                "- New instances (1000 calls): %.4fs\n" .
                "- Reused instance (1000 calls): %.4fs\n" .
                "- Improvement: %.2fx faster\n",
                $newInstanceTime,
                $reuseTime,
                $newInstanceTime / $reuseTime
            );
        }
    }
    
    /**
     * Benchmark null safety checks overhead.
     */
    public function testNullSafetyOverhead(): void
    {
        $compatibilityLayer = $this->createMock(CompatibilityLayer::class);
        $reflectionProvider = $this->createMock(ReflectionProvider::class);
        
        $provider = new AttributeProvider($compatibilityLayer, $reflectionProvider);
        
        // Setup reflection provider to return null
        $reflectionProvider
            ->method('getMethodReflectionFromClassString')
            ->willReturn(null);
        
        // Benchmark null checks
        $iterations = 100000;
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $result = $provider->getFromString('TestController::testMethod', 'Hash');
        }
        
        $totalTime = microtime(true) - $startTime;
        
        // Null checks should have minimal overhead
        $this->assertLessThan(
            0.5,
            $totalTime,
            sprintf('Null safety checks took too long: %.4fs for %d iterations', $totalTime, $iterations)
        );
        
        if (getenv('CI')) {
            echo sprintf(
                "\nNull Safety Overhead:\n" .
                "- %d null checks: %.4fs\n" .
                "- Average per check: %.8fs\n",
                $iterations,
                $totalTime,
                $totalTime / $iterations
            );
        }
    }
    
    /**
     * Create a mock ReflectionMethod for testing.
     */
    private function createMockMethod(): \ReflectionMethod|\PHPUnit\Framework\MockObject\MockObject
    {
        $method = $this->getMockBuilder(\ReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDocComment', 'getDeclaringClass', 'getName', 'getAttributes'])
            ->getMock();
        
        // Mock getAttributes to return empty array
        $method->method('getAttributes')->willReturn([]);
        
        return $method;
    }
    
    /**
     * Create a mock ReflectionClass for testing.
     */
    private function createMockReflectionClass(): \ReflectionClass|\PHPUnit\Framework\MockObject\MockObject
    {
        $mock = $this->getMockBuilder(\ReflectionClass::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName'])
            ->getMock();
        
        $mock->method('getName')->willReturn('TestClass');
        
        return $mock;
    }
}