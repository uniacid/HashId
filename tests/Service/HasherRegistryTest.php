<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Service\HasherRegistry;
use Pgs\HashIdBundle\Exception\HasherNotFoundException;
use Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter;

/**
 * Test suite for HasherRegistry service managing multiple hashers.
 * 
 * @covers \Pgs\HashIdBundle\Service\HasherRegistry
 */
class HasherRegistryTest extends TestCase
{
    private HasherRegistry $registry;
    
    protected function setUp(): void
    {
        $this->registry = new HasherRegistry();
    }
    
    /**
     * Test that default hasher is always available.
     */
    public function testDefaultHasherAlwaysAvailable(): void
    {
        $converter = $this->registry->getConverter('default');
        $this->assertInstanceOf(HashidsConverter::class, $converter);
        
        // Test encoding and decoding
        $encoded = $converter->encode(123);
        $this->assertIsString($encoded);
        $this->assertNotEquals('123', $encoded);
        
        $decoded = $converter->decode($encoded);
        $this->assertEquals(123, $decoded);
    }
    
    /**
     * Test registering a new hasher configuration.
     */
    public function testRegisterNewHasher(): void
    {
        $this->registry->registerHasher('secure', [
            'salt' => 'secure-salt-123',
            'min_hash_length' => 20,
            'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        ]);
        
        $converter = $this->registry->getConverter('secure');
        $this->assertInstanceOf(HashidsConverter::class, $converter);
        
        // Verify different encoding than default
        $defaultConverter = $this->registry->getConverter('default');
        $value = 456;
        
        $defaultEncoded = $defaultConverter->encode($value);
        $secureEncoded = $converter->encode($value);
        
        $this->assertNotEquals($defaultEncoded, $secureEncoded);
    }
    
    /**
     * Test registering multiple hashers.
     */
    public function testRegisterMultipleHashers(): void
    {
        $configs = [
            'public' => [
                'salt' => 'public',
                'min_hash_length' => 5,
            ],
            'secure' => [
                'salt' => 'very-secure',
                'min_hash_length' => 25,
            ],
            'custom' => [
                'salt' => 'custom-salt',
                'min_hash_length' => 15,
                'alphabet' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
            ],
        ];
        
        foreach ($configs as $name => $config) {
            $this->registry->registerHasher($name, $config);
        }
        
        // Verify all hashers are available
        foreach (array_keys($configs) as $name) {
            $converter = $this->registry->getConverter($name);
            $this->assertInstanceOf(HashidsConverter::class, $converter);
        }
        
        // Verify they produce different outputs
        $value = 789;
        $encodings = [];
        
        foreach (array_keys($configs) as $name) {
            $converter = $this->registry->getConverter($name);
            $encodings[$name] = $converter->encode($value);
        }
        
        // All encodings should be different
        $uniqueEncodings = array_unique($encodings);
        $this->assertCount(count($configs), $uniqueEncodings);
    }
    
    /**
     * Test that requesting non-existent hasher throws exception.
     */
    public function testNonExistentHasherThrowsException(): void
    {
        $this->expectException(\Pgs\HashIdBundle\Exception\HashIdException::class);
        $this->expectExceptionMessage('Hasher "non_existent" not found');
        
        $this->registry->getConverter('non_existent');
    }
    
    /**
     * Test lazy loading of hashers.
     */
    public function testLazyLoadingOfHashers(): void
    {
        $this->registry->registerHasher('lazy', [
            'salt' => 'lazy-salt',
            'min_hash_length' => 10,
        ]);
        
        // Get the same converter twice - should return cached instance
        $converter1 = $this->registry->getConverter('lazy');
        $converter2 = $this->registry->getConverter('lazy');
        
        $this->assertSame($converter1, $converter2);
    }
    
    /**
     * Test environment variable support in configuration.
     */
    public function testEnvironmentVariableSupport(): void
    {
        // Set environment variable
        $_ENV['TEST_HASHID_SALT'] = 'env-salt-value';
        
        $this->registry->registerHasher('env_test', [
            'salt' => '%env(TEST_HASHID_SALT)%',
            'min_hash_length' => 12,
        ]);
        
        $converter = $this->registry->getConverter('env_test');
        $encoded = $converter->encode(999);
        
        // Clean up
        unset($_ENV['TEST_HASHID_SALT']);
        
        $this->assertIsString($encoded);
        $this->assertNotEquals('999', $encoded);
    }
    
    /**
     * Test validation of hasher configuration.
     */
    public function testValidateHasherConfiguration(): void
    {
        // Test invalid min_hash_length
        $this->expectException(\Pgs\HashIdBundle\Exception\HashIdException::class);
        $this->expectExceptionMessage('Must be non-negative integer');
        
        $this->registry->registerHasher('invalid', [
            'salt' => 'test',
            'min_hash_length' => -1,
        ]);
    }
    
    /**
     * Test validation of alphabet configuration.
     */
    public function testValidateAlphabetConfiguration(): void
    {
        // Test alphabet with too few unique characters
        $this->expectException(\Pgs\HashIdBundle\Exception\HashIdException::class);
        $this->expectExceptionMessage('Must contain at least 16 unique characters');
        
        $this->registry->registerHasher('short_alphabet', [
            'salt' => 'test',
            'alphabet' => 'abcdefgh', // Only 8 characters
        ]);
    }
    
    /**
     * Test getting all registered hasher names.
     */
    public function testGetRegisteredHasherNames(): void
    {
        $this->registry->registerHasher('hasher1', ['salt' => 'salt1']);
        $this->registry->registerHasher('hasher2', ['salt' => 'salt2']);
        
        $names = $this->registry->getHasherNames();
        
        $this->assertContains('default', $names);
        $this->assertContains('hasher1', $names);
        $this->assertContains('hasher2', $names);
    }
    
    /**
     * Test hasher exists check.
     */
    public function testHasHasher(): void
    {
        $this->assertTrue($this->registry->hasHasher('default'));
        $this->assertFalse($this->registry->hasHasher('non_existent'));
        
        $this->registry->registerHasher('custom', ['salt' => 'custom']);
        $this->assertTrue($this->registry->hasHasher('custom'));
    }
    
    /**
     * Test overriding default hasher configuration.
     */
    public function testOverrideDefaultHasher(): void
    {
        $defaultConverter1 = $this->registry->getConverter('default');
        $encoded1 = $defaultConverter1->encode(123);
        
        // Override default configuration
        $this->registry->registerHasher('default', [
            'salt' => 'new-default-salt',
            'min_hash_length' => 15,
        ]);
        
        $defaultConverter2 = $this->registry->getConverter('default');
        $encoded2 = $defaultConverter2->encode(123);
        
        // Encodings should be different due to different salt
        $this->assertNotEquals($encoded1, $encoded2);
    }
    
    /**
     * Test bulk registration of hashers.
     */
    public function testBulkRegisterHashers(): void
    {
        $configs = [
            'api' => ['salt' => 'api-salt', 'min_hash_length' => 8],
            'admin' => ['salt' => 'admin-salt', 'min_hash_length' => 20],
            'public' => ['salt' => 'public-salt', 'min_hash_length' => 5],
        ];
        
        $this->registry->registerHashers($configs);
        
        foreach (array_keys($configs) as $name) {
            $this->assertTrue($this->registry->hasHasher($name));
        }
    }
}