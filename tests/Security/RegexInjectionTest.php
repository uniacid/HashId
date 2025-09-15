<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Service\CompatibilityLayer;

/**
 * Security tests for regex injection and ReDoS attack prevention.
 * 
 * @covers \Pgs\HashIdBundle\Service\CompatibilityLayer
 */
class RegexInjectionTest extends TestCase
{
    private CompatibilityLayer $compatibilityLayer;
    
    protected function setUp(): void
    {
        $this->compatibilityLayer = new CompatibilityLayer();
    }
    
    /**
     * Test that extremely long docblocks are rejected to prevent ReDoS.
     */
    public function testRejectsExtremelyLongDocblocks(): void
    {
        $method = $this->createMockMethod();
        
        // Create a docblock that exceeds the maximum allowed length
        $longString = str_repeat('a', 11000); // Over 10000 character limit
        $docblock = "/**\n * @Hash(\"$longString\")\n */";
        
        $method->method('getDocComment')->willReturn($docblock);
        
        // Set up error handler to catch the warning
        $warningTriggered = false;
        set_error_handler(function ($errno, $errstr) use (&$warningTriggered) {
            if (strpos($errstr, 'Docblock exceeds maximum allowed length') !== false) {
                $warningTriggered = true;
            }
            return true;
        }, E_USER_WARNING);
        
        $result = $this->compatibilityLayer->extractHashConfiguration($method);
        
        restore_error_handler();
        
        $this->assertNull($result);
        $this->assertTrue($warningTriggered, 'Should trigger warning for oversized docblock');
    }
    
    /**
     * Test protection against ReDoS attack patterns.
     */
    public function testProtectsAgainstReDosPatterns(): void
    {
        $method = $this->createMockMethod();
        
        // ReDoS attack pattern with nested repetitions
        $redosPattern = str_repeat('(', 100) . str_repeat(')', 100);
        $docblock = "/**\n * @Hash($redosPattern)\n */";
        
        $method->method('getDocComment')->willReturn($docblock);
        
        // This should complete quickly without catastrophic backtracking
        $startTime = microtime(true);
        $result = $this->compatibilityLayer->extractHashConfiguration($method);
        $executionTime = microtime(true) - $startTime;
        
        $this->assertNull($result);
        $this->assertLessThan(0.1, $executionTime, 'Should complete quickly without backtracking');
    }
    
    /**
     * Test rejection of suspicious characters in annotations.
     */
    public function testRejectsSuspiciousCharacters(): void
    {
        $suspiciousPatterns = [
            '<script>alert("xss")</script>',
            '\\x00\\x01\\x02',
            '\\0\\0\\0',
            '<>',
            'param1<script>',
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            $method = $this->createMockMethod();
            $docblock = "/**\n * @Hash(\"$pattern\")\n */";
            $method->method('getDocComment')->willReturn($docblock);
            
            // Set up error handler to catch the warning
            $warningTriggered = false;
            set_error_handler(function ($errno, $errstr) use (&$warningTriggered) {
                if (strpos($errstr, 'Invalid characters detected') !== false) {
                    $warningTriggered = true;
                }
                return true;
            }, E_USER_WARNING);
            
            $result = $this->compatibilityLayer->extractHashConfiguration($method);
            
            restore_error_handler();
            
            $this->assertNull($result, "Should reject pattern: $pattern");
            $this->assertTrue($warningTriggered, "Should trigger warning for pattern: $pattern");
        }
    }
    
    /**
     * Test that valid annotations are still processed correctly.
     */
    public function testAcceptsValidAnnotations(): void
    {
        $validPatterns = [
            ['annotation' => '@Hash("id")', 'expected' => ['id']],
            ['annotation' => '@Hash("userId")', 'expected' => ['userId']],
            ['annotation' => '@Hash("order_id")', 'expected' => ['order_id']],
            ['annotation' => '@Hash({"id", "userId"})', 'expected' => ['id', 'userId']],
        ];
        
        foreach ($validPatterns as $test) {
            $method = $this->createMockMethod();
            $docblock = "/**\n * {$test['annotation']}\n */";
            $method->method('getDocComment')->willReturn($docblock);
            $method->method('getDeclaringClass')->willReturn($this->createMockReflectionClass());
            $method->method('getName')->willReturn('testMethod');
            
            $result = $this->compatibilityLayer->extractHashConfiguration($method);
            
            $this->assertEquals($test['expected'], $result, "Failed for: {$test['annotation']}");
        }
    }
    
    /**
     * Test parameter count limits.
     */
    public function testRejectsTooManyParameters(): void
    {
        $method = $this->createMockMethod();
        
        // Create annotation with more than 20 parameters
        $params = array_map(fn($i) => "\"param$i\"", range(1, 25));
        $paramString = implode(', ', $params);
        $docblock = "/**\n * @Hash({$paramString})\n */";
        
        $method->method('getDocComment')->willReturn($docblock);
        
        // Set up error handler to catch the warning
        $warningTriggered = false;
        set_error_handler(function ($errno, $errstr) use (&$warningTriggered) {
            if (strpos($errstr, 'Too many parameters') !== false) {
                $warningTriggered = true;
            }
            return true;
        }, E_USER_WARNING);
        
        $result = $this->compatibilityLayer->extractHashConfiguration($method);
        
        restore_error_handler();
        
        $this->assertNull($result);
        $this->assertTrue($warningTriggered, 'Should trigger warning for too many parameters');
    }
    
    /**
     * Test parameter name validation.
     */
    public function testValidatesParameterNames(): void
    {
        $invalidNames = [
            'param-name',  // Contains hyphen
            'param name',  // Contains space
            'param!',      // Contains special character
            '123param',    // Starts with number (though this might be valid in some contexts)
            'param@user',  // Contains @
        ];
        
        foreach ($invalidNames as $invalidName) {
            $method = $this->createMockMethod();
            $docblock = "/**\n * @Hash({\"$invalidName\"})\n */";
            $method->method('getDocComment')->willReturn($docblock);
            $method->method('getDeclaringClass')->willReturn($this->createMockReflectionClass());
            $method->method('getName')->willReturn('testMethod');
            
            $result = $this->compatibilityLayer->extractHashConfiguration($method);
            
            // Invalid names should be filtered out
            $this->assertTrue(
                $result === null || !in_array($invalidName, $result, true),
                "Should reject invalid parameter name: $invalidName"
            );
        }
    }
    
    /**
     * Test that whitespace normalization doesn't break valid annotations.
     */
    public function testWhitespaceNormalization(): void
    {
        $method = $this->createMockMethod();
        
        // Annotation with excessive whitespace
        $docblock = "/**\n *    @Hash(   \"id\"   )\n\n\n */";
        
        $method->method('getDocComment')->willReturn($docblock);
        $method->method('getDeclaringClass')->willReturn($this->createMockReflectionClass());
        $method->method('getName')->willReturn('testMethod');
        
        $result = $this->compatibilityLayer->extractHashConfiguration($method);
        
        $this->assertEquals(['id'], $result, 'Should handle excessive whitespace correctly');
    }
    
    /**
     * Test handling of empty and null inputs.
     */
    public function testHandlesEmptyInputs(): void
    {
        $method = $this->createMockMethod();
        
        // Test empty docblock
        $method->method('getDocComment')->willReturn('');
        $result = $this->compatibilityLayer->extractHashConfiguration($method);
        $this->assertNull($result, 'Should return null for empty docblock');
        
        // Test false docblock (no docblock)
        $method = $this->createMockMethod();
        $method->method('getDocComment')->willReturn(false);
        $result = $this->compatibilityLayer->extractHashConfiguration($method);
        $this->assertNull($result, 'Should return null for false docblock');
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
        
        // Mock getAttributes to return empty array (no attributes for annotation tests)
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