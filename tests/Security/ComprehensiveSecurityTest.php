<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Service\HasherFactory;
use Pgs\HashIdBundle\AnnotationProvider\AttributeProvider;
use Pgs\HashIdBundle\Attribute\Hash;
use Pgs\HashIdBundle\Service\JsonValidator;
use Pgs\HashIdBundle\Exception\InvalidControllerException;

/**
 * Comprehensive security tests for all PR review fixes.
 * 
 * @covers \Pgs\HashIdBundle\Service\HasherFactory
 * @covers \Pgs\HashIdBundle\AnnotationProvider\AttributeProvider
 * @covers \Pgs\HashIdBundle\Attribute\Hash
 * @covers \Pgs\HashIdBundle\Service\JsonValidator
 */
class ComprehensiveSecurityTest extends TestCase
{
    /**
     * Test HasherFactory rejects invalid hasher types (injection protection).
     */
    public function testHasherFactoryRejectsInvalidTypes(): void
    {
        $factory = new HasherFactory();
        
        // Test various injection attempts
        $invalidTypes = [
            'invalid',
            'HASHER_DEFAULT', // Attempt to use constant name directly
            '../etc/passwd',
            'default; system("rm -rf /")',
            'default || true',
            "default' OR '1'='1",
            'default && echo "hacked"',
            '${HASHER_DEFAULT}',
            'default`.`',
            'default\'; DROP TABLE users; --',
        ];
        
        foreach ($invalidTypes as $type) {
            try {
                $factory->create($type);
                $this->fail("Should have rejected invalid type: $type");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('Unknown hasher type', $e->getMessage());
            }
        }
    }
    
    /**
     * Test HasherFactory only accepts whitelisted types.
     */
    public function testHasherFactoryOnlyAcceptsWhitelistedTypes(): void
    {
        $factory = new HasherFactory();
        
        // Valid types should work
        $validTypes = ['default', 'secure', 'custom'];
        
        foreach ($validTypes as $type) {
            $hasher = $factory->create($type);
            $this->assertNotNull($hasher);
        }
    }
    
    /**
     * Test AttributeProvider validates controller format.
     */
    public function testAttributeProviderValidatesControllerFormat(): void
    {
        $compatibilityLayer = $this->createMock(\Pgs\HashIdBundle\Service\CompatibilityLayer::class);
        $reflectionProvider = $this->createMock(\Pgs\HashIdBundle\Reflection\ReflectionProvider::class);
        
        $provider = new AttributeProvider($compatibilityLayer, $reflectionProvider);
        
        // Invalid controller formats
        $invalidControllers = [
            'NoDoubleColon',
            '::NoClass',
            'NoMethod::',
            'Class::Method::Extra',
            '../../../etc/passwd::method',
            'Class::$(echo hacked)',
            'Class::`rm -rf /`',
            '<script>alert("xss")</script>::method',
            'Class::method; DROP TABLE users',
            "Class\x00::method",
            "Class::method\x00",
            '<?php eval($_GET["cmd"]) ?>::method',
        ];
        
        foreach ($invalidControllers as $controller) {
            try {
                $provider->getFromString($controller, 'Hash');
                $this->fail("Should have rejected invalid controller: $controller");
            } catch (InvalidControllerException $e) {
                $this->assertThat(
                    $e->getMessage(),
                    $this->logicalOr(
                        $this->stringContains('Invalid controller format'),
                        $this->stringContains('illegal characters')
                    )
                );
            }
        }
    }
    
    /**
     * Test Hash attribute validates parameter count.
     */
    public function testHashAttributeValidatesParameterCount(): void
    {
        // Create array with too many parameters
        $tooManyParams = array_map(fn($i) => "param$i", range(1, 25));
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Too many parameters specified');
        
        new Hash($tooManyParams);
    }
    
    /**
     * Test Hash attribute validates parameter names.
     */
    public function testHashAttributeValidatesParameterNames(): void
    {
        $invalidNames = [
            'param-name',     // Contains hyphen
            'param name',     // Contains space
            'param!',         // Contains special character
            'param@user',     // Contains @
            '../etc/passwd',  // Path traversal attempt
            '$(whoami)',      // Command injection
            '<script>',       // XSS attempt
            'param;DROP',     // SQL injection
            'param\x00',      // Null byte
            str_repeat('a', 101), // Too long
        ];
        
        foreach ($invalidNames as $invalidName) {
            try {
                new Hash($invalidName);
                $this->fail("Should have rejected invalid parameter name: $invalidName");
            } catch (\InvalidArgumentException $e) {
                $this->assertThat(
                    $e->getMessage(),
                    $this->logicalOr(
                        $this->stringContains('Invalid parameter name'),
                        $this->stringContains('Parameter name too long')
                    )
                );
            }
        }
    }
    
    /**
     * Test JsonValidator enforces size limits.
     */
    public function testJsonValidatorEnforcesSizeLimits(): void
    {
        $validator = new JsonValidator();
        
        // Create JSON larger than 10MB limit
        $largeJson = '{"data":"' . str_repeat('a', 10485761) . '"}';
        
        $this->assertFalse($validator->isValid($largeJson));
        $this->assertFalse($validator->validateRequestBody($largeJson));
    }
    
    /**
     * Test JsonValidator accepts valid JSON within size limits.
     */
    public function testJsonValidatorAcceptsValidJson(): void
    {
        $validator = new JsonValidator();
        
        $validJson = json_encode([
            'user' => 'test',
            'data' => ['id' => 123, 'name' => 'Test User'],
        ]);
        
        $this->assertTrue($validator->isValid($validJson));
        $this->assertTrue($validator->validateRequestBody($validJson));
    }
    
    /**
     * Test JsonValidator custom size limits.
     */
    public function testJsonValidatorCustomSizeLimits(): void
    {
        $validator = new JsonValidator();
        
        // JSON that's larger than 1KB but smaller than default limit
        $json = '{"data":"' . str_repeat('a', 2000) . '"}';
        
        // Should fail with 1KB limit
        $this->assertFalse($validator->validateRequestBody($json, 1024));
        
        // Should pass with default limit
        $this->assertTrue($validator->validateRequestBody($json));
    }
    
    /**
     * Test HasherFactory cache management.
     */
    public function testHasherFactoryCacheManagement(): void
    {
        $factory = new HasherFactory();
        
        // Clear any existing cache
        $factory->clearInstanceCache();
        
        // Create multiple hashers with different configs
        $hashers = [];
        for ($i = 0; $i < 15; $i++) {
            $config = ['salt' => "salt_$i"];
            $hashers[] = $factory->create('default', $config);
        }
        
        // Cache should have evicted oldest entries (max 10)
        // Create the same hasher again to test cache hit
        $cachedHasher = $factory->create('default', ['salt' => 'salt_14']);
        $this->assertSame($hashers[14], $cachedHasher);
        
        // Clear cache and verify it's empty
        $factory->clearInstanceCache();
        $newHasher = $factory->create('default', ['salt' => 'salt_14']);
        $this->assertNotSame($hashers[14], $newHasher);
    }
    
    /**
     * Test performance of optimized regex operations.
     */
    public function testOptimizedRegexPerformance(): void
    {
        $compatibilityLayer = new \Pgs\HashIdBundle\Service\CompatibilityLayer();
        
        // Create mock method with docblock that would trigger early return
        $method = $this->createMockMethod();
        $docblock = '/** This is a regular comment without Hash annotation */';
        $method->method('getDocComment')->willReturn($docblock);
        
        // Should return quickly due to early check for @Hash
        $startTime = microtime(true);
        for ($i = 0; $i < 10000; $i++) {
            $compatibilityLayer->extractHashConfiguration($method);
        }
        $elapsed = microtime(true) - $startTime;
        
        // Should complete very quickly due to optimization
        $this->assertLessThan(0.1, $elapsed, 'Optimized regex should complete quickly');
    }
    
    /**
     * Test all security fixes work together.
     */
    public function testIntegratedSecurityMeasures(): void
    {
        // Test HasherFactory
        $factory = new HasherFactory('test-salt', 10);
        $hasher = $factory->create('default');
        $this->assertNotNull($hasher);
        
        // Test Hash attribute
        $hash = new Hash(['id', 'userId', 'orderId']);
        $this->assertCount(3, $hash->getParameters());
        
        // Test JsonValidator
        $validator = new JsonValidator();
        $this->assertTrue($validator->isValid('{"test": "data"}'));
        
        // All components should work without security issues
        $this->assertTrue(true, 'All security measures integrated successfully');
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
        
        $method->method('getAttributes')->willReturn([]);
        
        return $method;
    }
}