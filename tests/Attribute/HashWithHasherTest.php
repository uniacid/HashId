<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Attribute\Hash;

/**
 * Test Hash attribute with hasher parameter support.
 * 
 * @covers \Pgs\HashIdBundle\Attribute\Hash
 */
class HashWithHasherTest extends TestCase
{
    /**
     * Test basic Hash attribute without hasher parameter (backward compatibility).
     */
    public function testBasicHashAttributeWithoutHasher(): void
    {
        $hash = new Hash('id');
        
        $this->assertEquals(['id'], $hash->getParameters());
        $this->assertEquals('default', $hash->getHasher());
    }
    
    /**
     * Test Hash attribute with array of parameters without hasher.
     */
    public function testHashAttributeWithArrayWithoutHasher(): void
    {
        $hash = new Hash(['id', 'userId']);
        
        $this->assertEquals(['id', 'userId'], $hash->getParameters());
        $this->assertEquals('default', $hash->getHasher());
    }
    
    /**
     * Test Hash attribute with single parameter and hasher.
     */
    public function testHashAttributeWithSingleParameterAndHasher(): void
    {
        $hash = new Hash('id', hasher: 'secure');
        
        $this->assertEquals(['id'], $hash->getParameters());
        $this->assertEquals('secure', $hash->getHasher());
    }
    
    /**
     * Test Hash attribute with array of parameters and hasher.
     */
    public function testHashAttributeWithArrayAndHasher(): void
    {
        $hash = new Hash(['id', 'userId'], hasher: 'public');
        
        $this->assertEquals(['id', 'userId'], $hash->getParameters());
        $this->assertEquals('public', $hash->getHasher());
    }
    
    /**
     * Test Hash attribute with named parameters.
     */
    public function testHashAttributeWithNamedParameters(): void
    {
        $hash = new Hash(parameters: ['orderId', 'customerId'], hasher: 'api');
        
        $this->assertEquals(['orderId', 'customerId'], $hash->getParameters());
        $this->assertEquals('api', $hash->getHasher());
    }
    
    /**
     * Test validation of hasher name.
     */
    public function testHasherNameValidation(): void
    {
        // Valid hasher names
        $validNames = ['default', 'secure', 'public', 'api_v1', 'admin-panel', 'user.profile'];
        
        foreach ($validNames as $name) {
            $hash = new Hash('id', hasher: $name);
            $this->assertEquals($name, $hash->getHasher());
        }
    }
    
    /**
     * Test invalid hasher name throws exception.
     */
    public function testInvalidHasherNameThrowsException(): void
    {
        $this->expectException(\Pgs\HashIdBundle\Exception\HashIdException::class);
        $this->expectExceptionMessage('Invalid parameter "hasher"');
        
        new Hash('id', hasher: 'invalid hasher!');
    }
    
    /**
     * Test empty hasher name defaults to 'default'.
     */
    public function testEmptyHasherNameDefaultsToDefault(): void
    {
        $hash = new Hash('id', hasher: '');
        $this->assertEquals('default', $hash->getHasher());
    }
    
    /**
     * Test that hasher name is trimmed.
     */
    public function testHasherNameIsTrimmed(): void
    {
        $hash = new Hash('id', hasher: '  secure  ');
        $this->assertEquals('secure', $hash->getHasher());
    }
    
    /**
     * Test maximum hasher name length.
     */
    public function testMaximumHasherNameLength(): void
    {
        $longName = str_repeat('a', 51); // 51 characters
        
        $this->expectException(\Pgs\HashIdBundle\Exception\HashIdException::class);
        $this->expectExceptionMessage('Hasher name too long');
        
        new Hash('id', hasher: $longName);
    }
    
    /**
     * Test that existing parameter validation still works with hasher.
     */
    public function testParameterValidationWithHasher(): void
    {
        // Too many parameters
        $this->expectException(\Pgs\HashIdBundle\Exception\HashIdException::class);
        $this->expectExceptionMessage('Too many parameters');
        
        $parameters = [];
        for ($i = 0; $i < 25; $i++) {
            $parameters[] = "param$i";
        }
        
        new Hash($parameters, hasher: 'secure');
    }
    
    /**
     * Test using Hash attribute in a controller-like scenario.
     */
    public function testHashAttributeUsageScenario(): void
    {
        // Simulate different security contexts
        $publicHash = new Hash('articleId', hasher: 'public');
        $secureHash = new Hash(['userId', 'token'], hasher: 'secure');
        $apiHash = new Hash('resourceId', hasher: 'api');
        
        $this->assertEquals('public', $publicHash->getHasher());
        $this->assertEquals('secure', $secureHash->getHasher());
        $this->assertEquals('api', $apiHash->getHasher());
        
        // Each should have different hasher configurations
        $this->assertNotEquals($publicHash->getHasher(), $secureHash->getHasher());
        $this->assertNotEquals($secureHash->getHasher(), $apiHash->getHasher());
    }
    
    /**
     * Test backward compatibility with reflection.
     */
    public function testBackwardCompatibilityWithReflection(): void
    {
        $hash = new Hash('id');
        
        $reflection = new \ReflectionClass($hash);
        $parametersProperty = $reflection->getProperty('parameters');
        
        $this->assertEquals(['id'], $parametersProperty->getValue($hash));
        
        // Check new hasher property exists
        $this->assertTrue($reflection->hasProperty('hasher'));
        $hasherProperty = $reflection->getProperty('hasher');
        $this->assertEquals('default', $hasherProperty->getValue($hash));
    }
}