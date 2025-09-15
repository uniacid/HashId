<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Service\HasherFactory;

/**
 * Test validation improvements in HasherFactory.
 * 
 * @covers \Pgs\HashIdBundle\Service\HasherFactory
 */
class HasherFactoryValidationTest extends TestCase
{
    /**
     * Test that negative min_length is rejected.
     */
    public function testRejectsNegativeMinLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum length must be non-negative');
        
        new HasherFactory(null, -1);
    }
    
    /**
     * Test that alphabet with too few unique characters is rejected.
     */
    public function testRejectsShortAlphabet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Alphabet must contain at least 16 unique characters');
        
        new HasherFactory(null, 10, 'abcdefgh'); // Only 8 unique chars
    }
    
    /**
     * Test that alphabet with duplicates counts unique characters correctly.
     */
    public function testCountsUniqueCharactersInAlphabet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Alphabet must contain at least 16 unique characters');
        
        // 30 characters but only 10 unique
        new HasherFactory(null, 10, 'aaaaabbbbbcccccdddddeeeeefffffg');
    }
    
    /**
     * Test that invalid cache size is rejected.
     */
    public function testRejectsInvalidCacheSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Max cache size must be positive');
        
        new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 0);
    }
    
    /**
     * Test that valid configuration is accepted.
     */
    public function testAcceptsValidConfiguration(): void
    {
        $factory = new HasherFactory(
            'test-salt',
            10,
            'abcdefghijklmnopqrstuvwxyz',
            100
        );
        
        $this->assertInstanceOf(HasherFactory::class, $factory);
        
        // Test that hasher can be created
        $hasher = $factory->create('default');
        $this->assertNotNull($hasher);
    }
    
    /**
     * Test that create method validates merged configuration.
     */
    public function testCreateValidatesMergedConfig(): void
    {
        $factory = new HasherFactory();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum length must be non-negative');
        
        $factory->create('default', ['min_length' => -5]);
    }
    
    /**
     * Test cache key optimization with json_encode.
     */
    public function testCacheKeyGeneration(): void
    {
        $factory = new HasherFactory();
        
        // Create multiple hashers with same config
        $hasher1 = $factory->create('default', ['salt' => 'test']);
        $hasher2 = $factory->create('default', ['salt' => 'test']);
        
        // Should return the same cached instance
        $this->assertSame($hasher1, $hasher2);
        
        // Different config should create new instance
        $hasher3 = $factory->create('default', ['salt' => 'different']);
        $this->assertNotSame($hasher1, $hasher3);
    }
    
    /**
     * Test configurable cache size.
     */
    public function testConfigurableCacheSize(): void
    {
        // Small cache size for testing
        $factory = new HasherFactory(null, 10, 'abcdefghijklmnopqrstuvwxyz', 3);
        
        // Create 5 different hashers
        $hashers = [];
        for ($i = 0; $i < 5; $i++) {
            $hashers[] = $factory->create('default', ['salt' => "salt_$i"]);
        }
        
        // First two should have been evicted
        $newHasher0 = $factory->create('default', ['salt' => 'salt_0']);
        $newHasher4 = $factory->create('default', ['salt' => 'salt_4']);
        
        // Hasher 0 was evicted, so should be new instance
        $this->assertNotSame($hashers[0], $newHasher0);
        
        // Hasher 4 is still in cache, should be same instance
        $this->assertSame($hashers[4], $newHasher4);
    }
}