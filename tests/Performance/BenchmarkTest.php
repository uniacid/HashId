<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Performance;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Service\HasherFactory;
use Pgs\HashIdBundle\Service\CompatibilityLayer;
use Pgs\HashIdBundle\AnnotationProvider\AttributeProvider;
use Pgs\HashIdBundle\Reflection\ReflectionProvider;
use Pgs\HashIdBundle\Config\HashIdConfigInterface;

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
    
    /**
     * Benchmark readonly property performance vs regular properties.
     * Tests PHP 8.3 readonly optimization benefits.
     * 
     * @group php83
     */
    public function testReadonlyPropertyPerformance(): void
    {
        // Test with regular class
        $regularObject = new class(salt: 'test', minLength: 10) {
            public string $salt;
            public int $minLength;
            
            public function __construct(string $salt, int $minLength)
            {
                $this->salt = $salt;
                $this->minLength = $minLength;
            }
            
            public function getSalt(): string
            {
                return $this->salt;
            }
            
            public function getMinLength(): int
            {
                return $this->minLength;
            }
        };
        
        // Test with readonly class
        $readonlyObject = new readonly class('test', 10) {
            public function __construct(
                public readonly string $salt,
                public readonly int $minLength
            ) {}
            
            public function getSalt(): string
            {
                return $this->salt;
            }
            
            public function getMinLength(): int
            {
                return $this->minLength;
            }
        };
        
        $iterations = 1000000;
        
        // Benchmark regular property access
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $salt = $regularObject->getSalt();
            $length = $regularObject->getMinLength();
        }
        $regularTime = microtime(true) - $startTime;
        
        // Benchmark readonly property access
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $salt = $readonlyObject->getSalt();
            $length = $readonlyObject->getMinLength();
        }
        $readonlyTime = microtime(true) - $startTime;
        
        // Readonly should have similar or better performance
        $this->assertLessThan(
            $regularTime * 1.1, // Allow 10% variance
            $readonlyTime,
            sprintf(
                'Readonly property access should not be significantly slower. Regular: %.4fs, Readonly: %.4fs',
                $regularTime,
                $readonlyTime
            )
        );
        
        if (getenv('CI')) {
            echo sprintf(
                "\nReadonly Property Performance:\n" .
                "- Regular properties (%d accesses): %.4fs\n" .
                "- Readonly properties (%d accesses): %.4fs\n" .
                "- Difference: %.2f%%\n",
                $iterations * 2,
                $regularTime,
                $iterations * 2,
                $readonlyTime,
                (($readonlyTime - $regularTime) / $regularTime) * 100
            );
        }
    }
    
    /**
     * Benchmark match expression performance vs if-else chains.
     * Tests PHP 8.3 match expression optimization.
     * 
     * @group php83
     */
    public function testMatchExpressionPerformance(): void
    {
        $types = ['int', 'bool', 'float', 'string', 'array', 'object', 'null', 'resource'];
        $iterations = 100000;
        
        // Function using if-else chain
        $ifElseFunction = function($value, string $type) {
            if ($type === 'int') {
                return (int) $value;
            } elseif ($type === 'bool') {
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif ($type === 'float') {
                return (float) $value;
            } elseif ($type === 'string') {
                return (string) $value;
            } elseif ($type === 'array') {
                return (array) $value;
            } elseif ($type === 'object') {
                return (object) $value;
            } elseif ($type === 'null') {
                return null;
            } else {
                return $value;
            }
        };
        
        // Function using match expression
        $matchFunction = function($value, string $type) {
            return match ($type) {
                'int' => (int) $value,
                'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'float' => (float) $value,
                'string' => (string) $value,
                'array' => (array) $value,
                'object' => (object) $value,
                'null' => null,
                default => $value,
            };
        };
        
        // Benchmark if-else chain
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $type = $types[$i % count($types)];
            $result = $ifElseFunction('123', $type);
        }
        $ifElseTime = microtime(true) - $startTime;
        
        // Benchmark match expression
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $type = $types[$i % count($types)];
            $result = $matchFunction('123', $type);
        }
        $matchTime = microtime(true) - $startTime;
        
        // Match should be faster or comparable
        $this->assertLessThan(
            $ifElseTime * 1.2, // Allow 20% variance
            $matchTime,
            sprintf(
                'Match expression should not be significantly slower. If-else: %.4fs, Match: %.4fs',
                $ifElseTime,
                $matchTime
            )
        );
        
        if (getenv('CI')) {
            echo sprintf(
                "\nMatch Expression Performance:\n" .
                "- If-else chain (%d iterations): %.4fs\n" .
                "- Match expression (%d iterations): %.4fs\n" .
                "- Improvement: %.2f%%\n",
                $iterations,
                $ifElseTime,
                $iterations,
                $matchTime,
                (($ifElseTime - $matchTime) / $ifElseTime) * 100
            );
        }
    }
    
    /**
     * Benchmark typed class constants performance.
     * Tests PHP 8.3 typed constants optimization.
     * 
     * @group php83
     */
    public function testTypedConstantsPerformance(): void
    {
        // Regular constants (simulated with static properties)
        $regularClass = new class {
            public static $MIN_LENGTH = 10;
            public static $MAX_LENGTH = 255;
            public static $DEFAULT_ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        };
        
        // Use the actual HashIdConfigInterface for typed constants
        // This tests real-world usage rather than a test interface
        
        $iterations = 1000000;
        
        // Benchmark regular property access
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $min = $regularClass::$MIN_LENGTH;
            $max = $regularClass::$MAX_LENGTH;
            $alphabet = $regularClass::$DEFAULT_ALPHABET;
        }
        $regularTime = microtime(true) - $startTime;
        
        // Benchmark typed constant access
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $min = HashIdConfigInterface::MIN_LENGTH;
            $max = HashIdConfigInterface::MAX_LENGTH;
            $alphabet = HashIdConfigInterface::DEFAULT_ALPHABET;
        }
        $typedTime = microtime(true) - $startTime;
        
        // Typed constants should be faster (direct access vs property lookup)
        $this->assertLessThan(
            $regularTime,
            $typedTime,
            sprintf(
                'Typed constants should be faster than static properties. Regular: %.4fs, Typed: %.4fs',
                $regularTime,
                $typedTime
            )
        );
        
        if (getenv('CI')) {
            echo sprintf(
                "\nTyped Constants Performance:\n" .
                "- Static properties (%d accesses): %.4fs\n" .
                "- Typed constants (%d accesses): %.4fs\n" .
                "- Improvement: %.2f%%\n",
                $iterations * 3,
                $regularTime,
                $iterations * 3,
                $typedTime,
                (($regularTime - $typedTime) / $regularTime) * 100
            );
        }
    }
    
    /**
     * Benchmark json_validate() performance vs json_decode validation.
     * Tests PHP 8.3 json_validate() optimization.
     * 
     * @group php83
     */
    public function testJsonValidatePerformance(): void
    {
        $validJson = json_encode([
            'hasher' => 'default',
            'salt' => 'test-salt-value',
            'min_length' => 10,
            'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
            'parameters' => ['id', 'userId', 'postId', 'commentId'],
        ]);
        
        $iterations = 100000;
        
        // Benchmark json_decode validation
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $result = json_decode($validJson);
            $isValid = (json_last_error() === JSON_ERROR_NONE);
        }
        $decodeTime = microtime(true) - $startTime;
        
        // Benchmark json_validate (PHP 8.3+)
        if (function_exists('json_validate')) {
            $startTime = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $isValid = json_validate($validJson);
            }
            $validateTime = microtime(true) - $startTime;
            
            // json_validate should be faster or comparable (no decoding overhead)
            // Note: For small JSON, the difference may be minimal
            $this->assertLessThan(
                $decodeTime * 1.2, // Allow up to 20% slower for small payloads
                $validateTime,
                sprintf(
                    'json_validate() should not be significantly slower. Decode: %.4fs, Validate: %.4fs',
                    $decodeTime,
                    $validateTime
                )
            );
            
            if (getenv('CI')) {
                echo sprintf(
                    "\nJSON Validation Performance:\n" .
                    "- json_decode validation (%d iterations): %.4fs\n" .
                    "- json_validate (%d iterations): %.4fs\n" .
                    "- Improvement: %.2fx faster\n",
                    $iterations,
                    $decodeTime,
                    $iterations,
                    $validateTime,
                    $decodeTime / $validateTime
                );
            }
        } else {
            $this->markTestSkipped('json_validate() not available (requires PHP 8.3+)');
        }
    }
    
    /**
     * Benchmark cache warmup performance with readonly optimizations.
     * Tests overall system performance with PHP 8.3 features.
     * 
     * @group php83
     */
    public function testCacheWarmupPerformance(): void
    {
        $factory = new HasherFactory('test-salt', 10);
        $iterations = 10000;
        
        // Simulate cache warmup scenario
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            // Clear caches to simulate cold start
            if ($i % 100 === 0) {
                HasherFactory::clearCache();
            }
            
            // Typical operations during request
            $converter = $factory->createConverter('default', [
                'salt' => 'test-' . ($i % 10),
                'min_length' => 10,
            ]);
            $encoded = $converter->encode($i);
            $decoded = $converter->decode($encoded);
        }
        $totalTime = microtime(true) - $startTime;
        
        // Should complete within reasonable time
        $this->assertLessThan(
            2.0,
            $totalTime,
            sprintf('Cache warmup took too long: %.4fs for %d iterations', $totalTime, $iterations)
        );
        
        if (getenv('CI')) {
            echo sprintf(
                "\nCache Warmup Performance:\n" .
                "- Total time (%d iterations with periodic cache clear): %.4fs\n" .
                "- Average per iteration: %.6fs\n" .
                "- Operations per second: %.0f\n",
                $iterations,
                $totalTime,
                $totalTime / $iterations,
                $iterations / $totalTime
            );
        }
    }
}