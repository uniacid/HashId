<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Service;

use InvalidArgumentException;
use Pgs\HashIdBundle\ParametersProcessor\Converter\ConverterInterface;
use Pgs\HashIdBundle\Service\HasherFactory;
use Pgs\HashIdBundle\Service\HasherInterface;
use Pgs\HashIdBundle\Service\DefaultHasher;
use Pgs\HashIdBundle\Service\SecureHasher;
use Pgs\HashIdBundle\Service\CustomHasher;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for HasherFactory service.
 *
 * @covers \Pgs\HashIdBundle\Service\HasherFactory
 * @covers \Pgs\HashIdBundle\Service\DefaultHasher
 * @covers \Pgs\HashIdBundle\Service\SecureHasher
 * @covers \Pgs\HashIdBundle\Service\CustomHasher
 */
class HasherFactoryTest extends TestCase
{
    /**
     * Test creating default hasher.
     */
    public function testCreateDefaultHasher(): void
    {
        $factory = new HasherFactory();
        
        $hasher = $factory->create();
        
        $this->assertInstanceOf(HasherInterface::class, $hasher);
        $this->assertInstanceOf(DefaultHasher::class, $hasher);
        
        // Test that it can encode/decode
        $encoded = $hasher->encode(123);
        $this->assertNotEmpty($encoded);
        $this->assertIsString($encoded);
        
        $decoded = $hasher->decode($encoded);
        $this->assertSame(123, $decoded);
    }
    
    /**
     * Test creating secure hasher.
     */
    public function testCreateSecureHasher(): void
    {
        $factory = new HasherFactory();
        
        $hasher = $factory->create('secure');
        
        $this->assertInstanceOf(HasherInterface::class, $hasher);
        $this->assertInstanceOf(SecureHasher::class, $hasher);
        
        // Test encoding - secure hasher adds timestamp
        $encoded = $hasher->encode(456);
        $this->assertNotEmpty($encoded);
        $this->assertIsString($encoded);
        
        // Should be longer due to minimum length
        $this->assertGreaterThanOrEqual(20, strlen($encoded));
        
        $decoded = $hasher->decode($encoded);
        $this->assertSame(456, $decoded);
    }
    
    /**
     * Test creating custom hasher.
     */
    public function testCreateCustomHasher(): void
    {
        $factory = new HasherFactory();
        
        $hasher = $factory->create('custom');
        
        $this->assertInstanceOf(HasherInterface::class, $hasher);
        $this->assertInstanceOf(CustomHasher::class, $hasher);
        
        $encoded = $hasher->encode(789);
        $this->assertNotEmpty($encoded);
        
        $decoded = $hasher->decode($encoded);
        $this->assertSame(789, $decoded);
    }
    
    /**
     * Test creating hasher with custom configuration.
     */
    public function testCreateHasherWithCustomConfig(): void
    {
        $factory = new HasherFactory('custom_salt', 15);
        
        $hasher = $factory->create('default', [
            'min_length' => 20,
        ]);
        
        $encoded = $hasher->encode(999);
        
        // Should respect the overridden min_length
        $this->assertGreaterThanOrEqual(20, strlen($encoded));
    }
    
    /**
     * Test invalid hasher type throws exception.
     */
    public function testInvalidHasherTypeThrowsException(): void
    {
        $factory = new HasherFactory();
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown hasher type "invalid"');
        
        $factory->create('invalid');
    }
    
    /**
     * Test factory constructor validation.
     */
    public function testFactoryConstructorValidation(): void
    {
        // Test negative min length
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum length must be non-negative');
        
        new HasherFactory(null, -1);
    }
    
    /**
     * Test alphabet validation.
     */
    public function testAlphabetValidation(): void
    {
        // Test too short alphabet
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Alphabet must contain at least 16 unique characters');
        
        new HasherFactory(null, 10, 'abcde');
    }
    
    /**
     * Test get available types.
     */
    public function testGetAvailableTypes(): void
    {
        $factory = new HasherFactory();
        
        $types = $factory->getAvailableTypes();
        
        $this->assertIsArray($types);
        $this->assertContains('default', $types);
        $this->assertContains('secure', $types);
        $this->assertContains('custom', $types);
    }
    
    /**
     * Test cache behavior.
     */
    public function testCacheBehavior(): void
    {
        $factory = new HasherFactory();
        
        // Create same hasher twice
        $hasher1 = $factory->create('default');
        $hasher2 = $factory->create('default');
        
        // Should return the same cached instance
        $this->assertSame($hasher1, $hasher2);
        
        // Different configuration should create different instance
        $hasher3 = $factory->create('default', ['min_length' => 30]);
        $this->assertNotSame($hasher1, $hasher3);
    }
    
    /**
     * Test clear cache functionality.
     */
    public function testClearCache(): void
    {
        // Clear static cache
        HasherFactory::clearCache();
        
        $factory = new HasherFactory();
        
        // Create hasher, then clear instance cache
        $hasher1 = $factory->create('default');
        $factory->clearInstanceCache();
        
        // Should create new instance after cache clear
        $hasher2 = $factory->create('default');
        $this->assertNotSame($hasher1, $hasher2);
    }
    
    /**
     * Test preload configuration.
     */
    public function testPreloadConfiguration(): void
    {
        $factory = new HasherFactory();
        
        // Preload configuration
        $cacheKey = $factory->preloadConfiguration('default', ['min_length' => 25]);
        
        $this->assertIsString($cacheKey);
        $this->assertNotEmpty($cacheKey);
        
        // Creating with same config should use preloaded
        $hasher = $factory->create('default', ['min_length' => 25]);
        $this->assertInstanceOf(HasherInterface::class, $hasher);
    }
    
    /**
     * Test createConverter method.
     */
    public function testCreateConverter(): void
    {
        $factory = new HasherFactory();
        
        $converter = $factory->createConverter('default');
        
        $this->assertInstanceOf(ConverterInterface::class, $converter);
        
        // Test it works
        $encoded = $converter->encode(123);
        $this->assertNotEmpty($encoded);
        
        $decoded = $converter->decode($encoded);
        $this->assertSame(123, $decoded);
    }
    
    /**
     * Test non-numeric encoding.
     */
    public function testNonNumericEncoding(): void
    {
        $factory = new HasherFactory();
        $hasher = $factory->create();
        
        // Non-numeric values should be returned as string
        $result = $hasher->encode('not-a-number');
        $this->assertSame('not-a-number', $result);
    }
    
    /**
     * Test decode invalid hash.
     */
    public function testDecodeInvalidHash(): void
    {
        $factory = new HasherFactory();
        $hasher = $factory->create();
        
        // Invalid hash should return original value
        $result = $hasher->decode('invalid-hash');
        $this->assertSame('invalid-hash', $result);
    }
}