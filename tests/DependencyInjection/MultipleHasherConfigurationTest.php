<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Pgs\HashIdBundle\DependencyInjection\Configuration;
use Pgs\HashIdBundle\DependencyInjection\PgsHashIdExtension;

/**
 * Test multiple hasher configuration support.
 * 
 * @covers \Pgs\HashIdBundle\DependencyInjection\Configuration
 * @covers \Pgs\HashIdBundle\DependencyInjection\PgsHashIdExtension
 */
class MultipleHasherConfigurationTest extends TestCase
{
    private Configuration $configuration;
    private Processor $processor;
    
    protected function setUp(): void
    {
        $this->configuration = new Configuration();
        $this->processor = new Processor();
    }
    
    /**
     * Test single hasher configuration (backward compatibility).
     */
    public function testSingleHasherBackwardCompatibility(): void
    {
        $config = [
            'converter' => [
                'hashids' => [
                    'salt' => 'my-salt',
                    'min_hash_length' => 10,
                    'alphabet' => 'abcdefghijklmnopqrstuvwxyz',
                ],
            ],
        ];
        
        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config]);
        
        $this->assertArrayHasKey('converter', $processedConfig);
        $this->assertArrayHasKey('hashids', $processedConfig['converter']);
        $this->assertEquals('my-salt', $processedConfig['converter']['hashids']['salt']);
    }
    
    /**
     * Test multiple hasher configuration.
     */
    public function testMultipleHasherConfiguration(): void
    {
        $config = [
            'hashers' => [
                'default' => [
                    'salt' => 'default-salt',
                    'min_hash_length' => 10,
                    'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
                ],
                'secure' => [
                    'salt' => 'secure-salt',
                    'min_hash_length' => 20,
                    'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%',
                ],
                'public' => [
                    'salt' => 'public-salt',
                    'min_hash_length' => 5,
                ],
            ],
        ];
        
        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config]);
        
        $this->assertArrayHasKey('hashers', $processedConfig);
        $this->assertCount(3, $processedConfig['hashers']);
        $this->assertEquals('secure-salt', $processedConfig['hashers']['secure']['salt']);
    }
    
    /**
     * Test environment variable support in configuration.
     */
    public function testEnvironmentVariableConfiguration(): void
    {
        $config = [
            'hashers' => [
                'default' => [
                    'salt' => '%env(HASHID_SALT)%',
                    'min_hash_length' => 10,
                ],
                'secure' => [
                    'salt' => '%env(HASHID_SECURE_SALT)%',
                    'min_hash_length' => '%env(int:HASHID_SECURE_LENGTH)%',
                ],
            ],
        ];
        
        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config]);
        
        $this->assertEquals('%env(HASHID_SALT)%', $processedConfig['hashers']['default']['salt']);
        $this->assertEquals('%env(HASHID_SECURE_SALT)%', $processedConfig['hashers']['secure']['salt']);
    }
    
    /**
     * Test extension loads multiple hashers into container.
     */
    public function testExtensionLoadsMultipleHashers(): void
    {
        $container = new ContainerBuilder();
        $extension = new PgsHashIdExtension();
        
        $config = [
            'hashers' => [
                'default' => [
                    'salt' => 'default-salt',
                    'min_hash_length' => 10,
                ],
                'api' => [
                    'salt' => 'api-salt',
                    'min_hash_length' => 8,
                ],
            ],
        ];
        
        $extension->load([$config], $container);
        
        // Check that parameters are set for each hasher
        $this->assertTrue($container->hasParameter('pgs_hash_id.hashers.default.salt'));
        $this->assertEquals('default-salt', $container->getParameter('pgs_hash_id.hashers.default.salt'));
        
        $this->assertTrue($container->hasParameter('pgs_hash_id.hashers.api.salt'));
        $this->assertEquals('api-salt', $container->getParameter('pgs_hash_id.hashers.api.salt'));
        
        // Check that HasherRegistry service is defined
        $this->assertTrue($container->hasDefinition('pgs_hash_id.hasher_registry'));
    }
    
    /**
     * Test default values for hashers.
     */
    public function testDefaultHasherValues(): void
    {
        $config = [
            'hashers' => [
                'minimal' => [
                    'salt' => 'test-salt',
                ],
            ],
        ];
        
        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config]);
        
        $this->assertArrayHasKey('minimal', $processedConfig['hashers']);
        $this->assertEquals('test-salt', $processedConfig['hashers']['minimal']['salt']);
        
        // Should have default values for other properties
        $this->assertArrayHasKey('min_hash_length', $processedConfig['hashers']['minimal']);
        $this->assertArrayHasKey('alphabet', $processedConfig['hashers']['minimal']);
    }
    
    /**
     * Test that both old and new configuration formats work together.
     */
    public function testMixedConfigurationSupport(): void
    {
        $config = [
            // Old format for backward compatibility
            'converter' => [
                'hashids' => [
                    'salt' => 'legacy-salt',
                    'min_hash_length' => 10,
                ],
            ],
            // New format for multiple hashers
            'hashers' => [
                'secure' => [
                    'salt' => 'secure-salt',
                    'min_hash_length' => 20,
                ],
            ],
        ];
        
        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config]);
        
        // Both configurations should be present
        $this->assertArrayHasKey('converter', $processedConfig);
        $this->assertArrayHasKey('hashers', $processedConfig);
        
        $this->assertEquals('legacy-salt', $processedConfig['converter']['hashids']['salt']);
        $this->assertEquals('secure-salt', $processedConfig['hashers']['secure']['salt']);
    }
    
    /**
     * Test validation of hasher names.
     */
    public function testHasherNameValidation(): void
    {
        $config = [
            'hashers' => [
                'valid_name' => ['salt' => 'test1'],
                'another-valid' => ['salt' => 'test2'],
                'also.valid' => ['salt' => 'test3'],
            ],
        ];
        
        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config]);
        
        $this->assertCount(3, $processedConfig['hashers']);
        $this->assertArrayHasKey('valid_name', $processedConfig['hashers']);
        $this->assertArrayHasKey('another-valid', $processedConfig['hashers']);
        $this->assertArrayHasKey('also.valid', $processedConfig['hashers']);
    }
    
    /**
     * Test that hasher configuration is properly merged.
     */
    public function testHasherConfigurationMerging(): void
    {
        $configs = [
            [
                'hashers' => [
                    'default' => [
                        'salt' => 'first-salt',
                        'min_hash_length' => 10,
                    ],
                ],
            ],
            [
                'hashers' => [
                    'default' => [
                        'salt' => 'overridden-salt',
                    ],
                    'new' => [
                        'salt' => 'new-salt',
                    ],
                ],
            ],
        ];
        
        $processedConfig = $this->processor->processConfiguration($this->configuration, $configs);
        
        // Default should be overridden
        $this->assertEquals('overridden-salt', $processedConfig['hashers']['default']['salt']);
        $this->assertEquals(10, $processedConfig['hashers']['default']['min_hash_length']); // Should keep first value
        
        // New hasher should be added
        $this->assertArrayHasKey('new', $processedConfig['hashers']);
        $this->assertEquals('new-salt', $processedConfig['hashers']['new']['salt']);
    }
}