<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Config;

/**
 * Immutable hasher configuration using PHP 8.1+ readonly properties.
 *
 * Represents a single hasher configuration with validation-ready properties.
 * This class ensures configuration immutability and type safety.
 *
 * @since 4.0.0
 */
readonly class HasherConfiguration
{
    /**
     * @param string $name         The unique name identifier for this hasher
     * @param string $salt         The salt value for hash generation
     * @param int    $minHashLength The minimum length of generated hashes
     * @param string $alphabet     The character set used for hash generation
     * @param bool   $enabled      Whether this hasher is enabled
     */
    public function __construct(
        public string $name,
        public string $salt,
        public int $minHashLength,
        public string $alphabet,
        public bool $enabled = true
    ) {
    }

    /**
     * Creates a HasherConfiguration from an array of configuration values.
     *
     * @param string $name   The hasher name
     * @param array  $config The configuration array
     *
     * @return self
     */
    public static function fromArray(string $name, array $config): self
    {
        return new self(
            name: $name,
            salt: $config['salt'] ?? '',
            minHashLength: $config['min_hash_length'] ?? HashIdConfigInterface::DEFAULT_MIN_LENGTH,
            alphabet: $config['alphabet'] ?? HashIdConfigInterface::DEFAULT_ALPHABET,
            enabled: $config['enabled'] ?? true
        );
    }

    /**
     * Converts the configuration to an array format.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'salt' => $this->salt,
            'min_hash_length' => $this->minHashLength,
            'alphabet' => $this->alphabet,
            'enabled' => $this->enabled,
        ];
    }

    /**
     * Checks if this configuration uses environment variables.
     *
     * @return bool
     */
    public function hasEnvironmentVariables(): bool
    {
        // minHashLength is always an int at this point, check only strings
        return str_contains($this->salt, '%env(')
            || str_contains($this->alphabet, '%env(');
    }

    /**
     * Creates a default configuration.
     *
     * @param string $name The hasher name
     *
     * @return self
     */
    public static function createDefault(string $name = 'default'): self
    {
        return new self(
            name: $name,
            salt: HashIdConfigInterface::DEFAULT_SALT,
            minHashLength: HashIdConfigInterface::DEFAULT_MIN_LENGTH,
            alphabet: HashIdConfigInterface::DEFAULT_ALPHABET,
            enabled: true
        );
    }
}