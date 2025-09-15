<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Config;

use Pgs\HashIdBundle\Exception\HashIdError;
use Pgs\HashIdBundle\Exception\HashIdException;

/**
 * Factory for creating and validating hasher configurations.
 *
 * Provides methods to create HasherConfiguration instances from various sources
 * with built-in validation to ensure configurations are valid before use.
 *
 * @since 4.0.0
 */
class ConfigurationFactory
{
    private ConfigurationValidator $validator;

    public function __construct(?ConfigurationValidator $validator = null)
    {
        $this->validator = $validator ?? new ConfigurationValidator();
    }

    /**
     * Creates a validated HasherConfiguration from an array.
     *
     * @param string $name   The hasher name
     * @param array  $config The configuration array
     * @param bool   $validate Whether to validate the configuration
     *
     * @return HasherConfiguration
     *
     * @throws HashIdException If validation is enabled and configuration is invalid
     */
    public function createFromArray(string $name, array $config, bool $validate = true): HasherConfiguration
    {
        $configuration = HasherConfiguration::fromArray($name, $config);

        if ($validate && !$this->containsEnvironmentVariables($config)) {
            $this->validator->validate($configuration);
        }

        return $configuration;
    }

    /**
     * Creates multiple hasher configurations from a configuration array.
     *
     * @param array $configs Array of hasher configurations indexed by name
     * @param bool  $validate Whether to validate configurations
     *
     * @return array<string, HasherConfiguration>
     *
     * @throws HashIdException If any configuration is invalid
     */
    public function createMultiple(array $configs, bool $validate = true): array
    {
        $configurations = [];

        foreach ($configs as $name => $config) {
            try {
                $configurations[$name] = $this->createFromArray($name, $config, $validate);
            } catch (HashIdException $e) {
                throw new HashIdException(
                    HashIdError::CONFIGURATION_ERROR,
                    sprintf('Invalid configuration for hasher "%s": %s', $name, $e->getMessage()),
                    0,
                    $e
                );
            }
        }

        return $configurations;
    }

    /**
     * Creates a HasherConfiguration with resolved environment variables.
     *
     * This method is used when environment variables have been resolved by the container.
     *
     * @param string $name         The hasher name
     * @param string $salt         The resolved salt value
     * @param int    $minHashLength The resolved minimum hash length
     * @param string $alphabet     The resolved alphabet
     * @param bool   $enabled      Whether the hasher is enabled
     *
     * @return HasherConfiguration
     *
     * @throws HashIdException If the resolved configuration is invalid
     */
    public function createResolved(
        string $name,
        string $salt,
        int $minHashLength,
        string $alphabet,
        bool $enabled = true
    ): HasherConfiguration {
        $configuration = new HasherConfiguration($name, $salt, $minHashLength, $alphabet, $enabled);
        $this->validator->validate($configuration);

        return $configuration;
    }

    /**
     * Migrates a legacy configuration to the new format.
     *
     * @param array $legacyConfig The legacy converter configuration
     *
     * @return HasherConfiguration
     */
    public function migrateFromLegacy(array $legacyConfig): HasherConfiguration
    {
        $config = [
            'salt' => $legacyConfig['salt'] ?? HashIdConfigInterface::DEFAULT_SALT,
            'min_hash_length' => $legacyConfig['min_hash_length'] ?? HashIdConfigInterface::DEFAULT_MIN_LENGTH,
            'alphabet' => $legacyConfig['alphabet'] ?? HashIdConfigInterface::DEFAULT_ALPHABET,
            'enabled' => true,
        ];

        return $this->createFromArray('default', $config, false);
    }

    /**
     * Checks if a configuration array contains environment variables.
     *
     * @param array $config The configuration array to check
     *
     * @return bool
     */
    private function containsEnvironmentVariables(array $config): bool
    {
        foreach ($config as $value) {
            if (is_string($value) && str_contains($value, '%env(')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validates that a configuration would be valid after environment resolution.
     *
     * @param HasherConfiguration $configuration The configuration to validate
     *
     * @return bool True if structurally valid
     */
    public function validateStructure(HasherConfiguration $configuration): bool
    {
        return $this->validator->isStructurallyValid($configuration);
    }

    /**
     * Gets predefined configuration templates.
     *
     * @return array<string, array>
     */
    public static function getTemplates(): array
    {
        return [
            'default' => [
                'salt' => '%env(HASHID_SALT)%',
                'min_hash_length' => 10,
                'alphabet' => HashIdConfigInterface::DEFAULT_ALPHABET,
            ],
            'secure' => [
                'salt' => '%env(HASHID_SECURE_SALT)%',
                'min_hash_length' => 20,
                'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*',
            ],
            'public' => [
                'salt' => '%env(HASHID_PUBLIC_SALT)%',
                'min_hash_length' => 5,
                'alphabet' => 'abcdefghijklmnopqrstuvwxyz1234567890',
            ],
            'api' => [
                'salt' => '%env(HASHID_API_SALT)%',
                'min_hash_length' => 12,
                'alphabet' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
            ],
        ];
    }

    /**
     * Creates a configuration from a template.
     *
     * @param string $templateName The template name
     * @param string $name        The hasher name
     *
     * @return HasherConfiguration
     *
     * @throws HashIdException If the template doesn't exist
     */
    public function createFromTemplate(string $templateName, string $name): HasherConfiguration
    {
        $templates = self::getTemplates();

        if (!isset($templates[$templateName])) {
            throw new HashIdException(
                HashIdError::CONFIGURATION_ERROR,
                sprintf('Configuration template "%s" does not exist', $templateName)
            );
        }

        return $this->createFromArray($name, $templates[$templateName], false);
    }
}