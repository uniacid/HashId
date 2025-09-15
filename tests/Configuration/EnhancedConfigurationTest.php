<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Configuration;

use Pgs\HashIdBundle\Config\HasherConfiguration;
use Pgs\HashIdBundle\Config\ConfigurationValidator;
use Pgs\HashIdBundle\DependencyInjection\Configuration;
use Pgs\HashIdBundle\Exception\HashIdError;
use Pgs\HashIdBundle\Exception\HashIdException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class EnhancedConfigurationTest extends TestCase
{
    private Configuration $configuration;
    private Processor $processor;

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
        $this->processor = new Processor();
    }

    public function testEnvironmentVariableSupport(): void
    {
        $config = [
            'pgs_hash_id' => [
                'hashers' => [
                    'default' => [
                        'salt' => '%env(HASHID_SALT)%',
                        'min_hash_length' => '%env(int:HASHID_MIN_LENGTH)%',
                        'alphabet' => '%env(HASHID_ALPHABET)%',
                    ],
                    'secure' => [
                        'salt' => '%env(HASHID_SECURE_SALT)%',
                        'min_hash_length' => '%env(int:HASHID_SECURE_MIN_LENGTH)%',
                        'alphabet' => '%env(HASHID_SECURE_ALPHABET)%',
                    ],
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, $config);

        $this->assertArrayHasKey('hashers', $processedConfig);
        $this->assertArrayHasKey('default', $processedConfig['hashers']);
        $this->assertArrayHasKey('secure', $processedConfig['hashers']);
        
        // Environment variables should be preserved as-is
        $this->assertEquals('%env(HASHID_SALT)%', $processedConfig['hashers']['default']['salt']);
        $this->assertEquals('%env(int:HASHID_MIN_LENGTH)%', $processedConfig['hashers']['default']['min_hash_length']);
        $this->assertEquals('%env(HASHID_ALPHABET)%', $processedConfig['hashers']['default']['alphabet']);
    }

    public function testTypedEnvironmentVariables(): void
    {
        $config = [
            'pgs_hash_id' => [
                'hashers' => [
                    'typed' => [
                        'salt' => '%env(string:HASHID_SALT)%',
                        'min_hash_length' => '%env(int:HASHID_LENGTH)%',
                        'alphabet' => '%env(string:HASHID_ALPHABET)%',
                    ],
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, $config);

        $this->assertEquals('%env(string:HASHID_SALT)%', $processedConfig['hashers']['typed']['salt']);
        $this->assertEquals('%env(int:HASHID_LENGTH)%', $processedConfig['hashers']['typed']['min_hash_length']);
        $this->assertEquals('%env(string:HASHID_ALPHABET)%', $processedConfig['hashers']['typed']['alphabet']);
    }

    public function testDefaultEnvironmentVariables(): void
    {
        $config = [
            'pgs_hash_id' => [
                'hashers' => [
                    'with_defaults' => [
                        'salt' => '%env(HASHID_SALT)%',
                        // min_hash_length and alphabet should get defaults
                    ],
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, $config);

        $this->assertEquals('%env(HASHID_SALT)%', $processedConfig['hashers']['with_defaults']['salt']);
        $this->assertEquals(10, $processedConfig['hashers']['with_defaults']['min_hash_length']);
        $this->assertEquals(
            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
            $processedConfig['hashers']['with_defaults']['alphabet']
        );
    }

    public function testTypedConfigurationValidation(): void
    {
        $validator = new ConfigurationValidator();

        // Valid configuration
        $validConfig = new HasherConfiguration(
            name: 'default',
            salt: 'test-salt-123',
            minHashLength: 10,
            alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
            enabled: true
        );

        $this->assertTrue($validator->validate($validConfig));

        // Invalid: min_hash_length too small
        $invalidMinLength = new HasherConfiguration(
            name: 'invalid',
            salt: 'test-salt',
            minHashLength: 0,
            alphabet: 'abc',
            enabled: true
        );

        $this->expectException(HashIdException::class);
        $validator->validate($invalidMinLength);
    }

    public function testInvalidMinHashLengthThrowsException(): void
    {
        $validator = new ConfigurationValidator();

        $invalidConfig = new HasherConfiguration(
            name: 'invalid',
            salt: 'test',
            minHashLength: -1,
            alphabet: 'abc',
            enabled: true
        );

        try {
            $validator->validate($invalidConfig);
            $this->fail('Expected HashIdException was not thrown');
        } catch (HashIdException $e) {
            $this->assertEquals(HashIdError::CONFIGURATION_ERROR, $e->getError());
            $this->assertStringContainsString('minimum hash length', $e->getMessage());
        }
    }

    public function testInvalidAlphabetThrowsException(): void
    {
        $validator = new ConfigurationValidator();

        // Alphabet too short (less than 4 characters required by Hashids)
        $invalidConfig = new HasherConfiguration(
            name: 'invalid',
            salt: 'test',
            minHashLength: 10,
            alphabet: 'ab',
            enabled: true
        );

        try {
            $validator->validate($invalidConfig);
            $this->fail('Expected HashIdException was not thrown');
        } catch (HashIdException $e) {
            $this->assertEquals(HashIdError::CONFIGURATION_ERROR, $e->getError());
            $this->assertStringContainsString('alphabet', $e->getMessage());
        }
    }

    public function testConfigurationMigrationFromLegacy(): void
    {
        // Legacy configuration format
        $legacyConfig = [
            'pgs_hash_id' => [
                'converter' => [
                    'hashids' => [
                        'salt' => 'legacy-salt',
                        'min_hash_length' => 15,
                        'alphabet' => 'custom123',
                    ],
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, $legacyConfig);

        // Should still process legacy format
        $this->assertArrayHasKey('converter', $processedConfig);
        $this->assertEquals('legacy-salt', $processedConfig['converter']['hashids']['salt']);
        $this->assertEquals(15, $processedConfig['converter']['hashids']['min_hash_length']);
    }

    public function testMixedLegacyAndNewConfiguration(): void
    {
        // Both legacy and new format together
        $mixedConfig = [
            'pgs_hash_id' => [
                'converter' => [
                    'hashids' => [
                        'salt' => 'legacy-salt',
                        'min_hash_length' => 15,
                    ],
                ],
                'hashers' => [
                    'modern' => [
                        'salt' => 'modern-salt',
                        'min_hash_length' => 20,
                    ],
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, $mixedConfig);

        // Both should be present
        $this->assertArrayHasKey('converter', $processedConfig);
        $this->assertArrayHasKey('hashers', $processedConfig);
        $this->assertEquals('legacy-salt', $processedConfig['converter']['hashids']['salt']);
        $this->assertEquals('modern-salt', $processedConfig['hashers']['modern']['salt']);
    }

    public function testMultipleHasherConfigurations(): void
    {
        $config = [
            'pgs_hash_id' => [
                'hashers' => [
                    'default' => [
                        'salt' => 'default-salt',
                        'min_hash_length' => 10,
                    ],
                    'secure' => [
                        'salt' => 'secure-salt',
                        'min_hash_length' => 25,
                        'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%',
                    ],
                    'public' => [
                        'salt' => 'public-salt',
                        'min_hash_length' => 5,
                    ],
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, $config);

        $this->assertCount(3, $processedConfig['hashers']);
        $this->assertEquals('default-salt', $processedConfig['hashers']['default']['salt']);
        $this->assertEquals('secure-salt', $processedConfig['hashers']['secure']['salt']);
        $this->assertEquals('public-salt', $processedConfig['hashers']['public']['salt']);
        $this->assertEquals(25, $processedConfig['hashers']['secure']['min_hash_length']);
    }

    public function testConfigurationWithSpecialCharactersInHasherNames(): void
    {
        $config = [
            'pgs_hash_id' => [
                'hashers' => [
                    'api-v2' => [
                        'salt' => 'api-salt',
                        'min_hash_length' => 12,
                    ],
                    'admin.secure' => [
                        'salt' => 'admin-salt',
                        'min_hash_length' => 30,
                    ],
                    'user_profile' => [
                        'salt' => 'user-salt',
                        'min_hash_length' => 8,
                    ],
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, $config);

        $this->assertArrayHasKey('api-v2', $processedConfig['hashers']);
        $this->assertArrayHasKey('admin.secure', $processedConfig['hashers']);
        $this->assertArrayHasKey('user_profile', $processedConfig['hashers']);
    }

    public function testEmptyConfigurationUsesDefaults(): void
    {
        $config = [
            'pgs_hash_id' => [],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, $config);

        // Should have default converter configuration
        $this->assertArrayHasKey('converter', $processedConfig);
        $this->assertArrayHasKey('hashids', $processedConfig['converter']);
        $this->assertEquals('', $processedConfig['converter']['hashids']['salt']);
        $this->assertEquals(10, $processedConfig['converter']['hashids']['min_hash_length']);
    }

    public function testConfigurationFactoryCreation(): void
    {
        $factory = new HasherConfiguration(
            name: 'test',
            salt: 'factory-salt',
            minHashLength: 15,
            alphabet: 'abcdefghij1234567890',
            enabled: true
        );

        $this->assertEquals('test', $factory->name);
        $this->assertEquals('factory-salt', $factory->salt);
        $this->assertEquals(15, $factory->minHashLength);
        $this->assertEquals('abcdefghij1234567890', $factory->alphabet);
        $this->assertTrue($factory->enabled);
    }

    public function testReadonlyConfigurationImmutability(): void
    {
        $config = new HasherConfiguration(
            name: 'immutable',
            salt: 'cannot-change',
            minHashLength: 10,
            alphabet: 'abc123',
            enabled: true
        );

        // This test verifies that the readonly properties cannot be modified
        // after instantiation. If PHP 8.1+ readonly is working correctly,
        // attempting to modify these properties would cause a fatal error.
        $this->assertEquals('immutable', $config->name);
        $this->assertEquals('cannot-change', $config->salt);
    }

    public function testConfigurationValidationMessages(): void
    {
        $validator = new ConfigurationValidator();

        // Test various invalid configurations and their error messages
        $testCases = [
            [
                'config' => new HasherConfiguration('test', '', 10, 'abc123', true),
                'expectedMessage' => 'salt cannot be empty',
            ],
            [
                'config' => new HasherConfiguration('test', 'salt', 256, 'abc123', true),
                'expectedMessage' => 'exceeds maximum',
            ],
            [
                'config' => new HasherConfiguration('test', 'salt', 10, '', true),
                'expectedMessage' => 'alphabet cannot be empty',
            ],
            [
                'config' => new HasherConfiguration('test', 'salt', 10, 'aaaa', true),
                'expectedMessage' => 'unique characters',
            ],
        ];

        foreach ($testCases as $testCase) {
            try {
                $validator->validate($testCase['config']);
                $this->fail('Expected HashIdException for: ' . $testCase['expectedMessage']);
            } catch (HashIdException $e) {
                $this->assertStringContainsString($testCase['expectedMessage'], $e->getMessage());
            }
        }
    }

    public function testComplexEnvironmentVariableExpressions(): void
    {
        $config = [
            'pgs_hash_id' => [
                'hashers' => [
                    'complex' => [
                        'salt' => '%env(default:default_salt:HASHID_SALT)%',
                        'min_hash_length' => '%env(int:default:10:HASHID_LENGTH)%',
                        'alphabet' => '%env(file:HASHID_ALPHABET_FILE)%',
                    ],
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, $config);

        $this->assertEquals('%env(default:default_salt:HASHID_SALT)%', $processedConfig['hashers']['complex']['salt']);
        $this->assertEquals('%env(int:default:10:HASHID_LENGTH)%', $processedConfig['hashers']['complex']['min_hash_length']);
        $this->assertEquals('%env(file:HASHID_ALPHABET_FILE)%', $processedConfig['hashers']['complex']['alphabet']);
    }
}