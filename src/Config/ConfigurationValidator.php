<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Config;

use Pgs\HashIdBundle\Exception\HashIdError;
use Pgs\HashIdBundle\Exception\HashIdException;

/**
 * Validates hasher configurations.
 *
 * Ensures hasher configurations meet the requirements for proper hash generation
 * and provides detailed error messages for invalid configurations.
 *
 * @since 4.0.0
 */
class ConfigurationValidator
{
    /**
     * Minimum required alphabet length for Hashids library.
     */
    private const MIN_ALPHABET_LENGTH = 4;

    /**
     * Maximum alphabet length to prevent performance issues.
     */
    private const MAX_ALPHABET_LENGTH = 256;

    /**
     * Validates a hasher configuration.
     *
     * @param HasherConfiguration $config The configuration to validate
     *
     * @return bool True if valid
     *
     * @throws HashIdException If the configuration is invalid
     */
    public function validate(HasherConfiguration $config): bool
    {
        $this->validateSalt($config->salt);
        $this->validateMinHashLength($config->minHashLength);
        $this->validateAlphabet($config->alphabet);

        return true;
    }

    /**
     * Validates the salt value.
     *
     * @param string $salt The salt to validate
     *
     * @throws HashIdException If the salt is invalid
     */
    private function validateSalt(string $salt): void
    {
        // Allow environment variables
        if (str_contains($salt, '%env(')) {
            return;
        }

        // Empty string is technically allowed for backward compatibility,
        // but we should warn in production
        if ($salt === '') {
            throw new HashIdException(
                HashIdError::CONFIGURATION_ERROR,
                'Hasher salt cannot be empty for production use'
            );
        }
    }

    /**
     * Validates the minimum hash length.
     *
     * @param int $minHashLength The minimum hash length to validate
     *
     * @throws HashIdException If the minimum hash length is invalid
     */
    private function validateMinHashLength(int $minHashLength): void
    {
        // Note: Environment variables are handled before validation,
        // so at this point we always have an integer

        if ($minHashLength < 0) {
            throw new HashIdException(
                HashIdError::CONFIGURATION_ERROR,
                sprintf('Invalid minimum hash length: %d. Must be non-negative', $minHashLength)
            );
        }

        if ($minHashLength > HashIdConfigInterface::MAX_LENGTH) {
            throw new HashIdException(
                HashIdError::CONFIGURATION_ERROR,
                sprintf(
                    'Minimum hash length %d exceeds maximum allowed length of %d',
                    $minHashLength,
                    HashIdConfigInterface::MAX_LENGTH
                )
            );
        }
    }

    /**
     * Validates the alphabet.
     *
     * @param string $alphabet The alphabet to validate
     *
     * @throws HashIdException If the alphabet is invalid
     */
    private function validateAlphabet(string $alphabet): void
    {
        // Allow environment variables
        if (str_contains($alphabet, '%env(')) {
            return;
        }

        if (empty($alphabet)) {
            throw new HashIdException(
                HashIdError::CONFIGURATION_ERROR,
                'Hasher alphabet cannot be empty'
            );
        }

        $length = strlen($alphabet);

        if ($length < self::MIN_ALPHABET_LENGTH) {
            throw new HashIdException(
                HashIdError::CONFIGURATION_ERROR,
                sprintf(
                    'Hasher alphabet must contain at least %d characters, %d given',
                    self::MIN_ALPHABET_LENGTH,
                    $length
                )
            );
        }

        if ($length > self::MAX_ALPHABET_LENGTH) {
            throw new HashIdException(
                HashIdError::CONFIGURATION_ERROR,
                sprintf(
                    'Hasher alphabet exceeds maximum length of %d characters, %d given',
                    self::MAX_ALPHABET_LENGTH,
                    $length
                )
            );
        }

        // Check for unique characters
        $uniqueChars = count_chars($alphabet, 3);
        if (strlen($uniqueChars) !== $length) {
            throw new HashIdException(
                HashIdError::CONFIGURATION_ERROR,
                'Hasher alphabet must contain only unique characters'
            );
        }
    }

    /**
     * Validates multiple configurations at once.
     *
     * @param array<string, HasherConfiguration> $configurations
     *
     * @return array<string, bool> Validation results per configuration
     */
    public function validateMultiple(array $configurations): array
    {
        $results = [];

        foreach ($configurations as $name => $config) {
            try {
                $results[$name] = $this->validate($config);
            } catch (HashIdException $e) {
                $results[$name] = false;
            }
        }

        return $results;
    }

    /**
     * Checks if a configuration would be valid after environment variable resolution.
     *
     * This method is useful for pre-validation when environment variables are not yet resolved.
     *
     * @param HasherConfiguration $config The configuration to check
     *
     * @return bool True if the configuration structure is valid
     */
    public function isStructurallyValid(HasherConfiguration $config): bool
    {
        // Check structural validity without validating actual values
        if (empty($config->name)) {
            return false;
        }

        // All properties are typed in the constructor, so they're guaranteed to be valid types
        // Just check for reasonable values
        if (!is_int($config->minHashLength)) {
            return false;
        }

        return true;
    }
}