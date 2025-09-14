<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Service;

use Hashids\Hashids;
use Pgs\HashIdBundle\Config\HashIdConfigInterface;
use Pgs\HashIdBundle\ParametersProcessor\Converter\ConverterInterface;
use Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter;

/**
 * Factory for creating different hasher implementations.
 *
 * Uses PHP 8.3 dynamic constant fetch for hasher selection,
 * allowing flexible hasher strategy configuration.
 *
 * @since 4.0.0
 */
class HasherFactory
{
    /**
     * Hasher class constants for dynamic selection.
     */
    public const HASHER_DEFAULT = DefaultHasher::class;
    public const HASHER_SECURE = SecureHasher::class;
    public const HASHER_CUSTOM = CustomHasher::class;

    /**
     * Configuration for different hasher types.
     */
    private const HASHER_CONFIGS = [
        'default' => [
            'salt' => '',
            'min_length' => 10,
            'alphabet' => HashIdConfigInterface::DEFAULT_ALPHABET,
        ],
        'secure' => [
            'salt' => null, // Will be generated
            'min_length' => 20,
            'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*',
        ],
        'custom' => [
            'salt' => null,
            'min_length' => null,
            'alphabet' => null,
        ],
    ];

    private ?string $defaultSalt;
    private int $defaultMinLength;
    private string $defaultAlphabet;
    
    /**
     * @var array<string>|null Cached available types to avoid repeated reflection
     */
    private static ?array $availableTypesCache = null;

    public function __construct(
        ?string $salt = null,
        int $minLength = HashIdConfigInterface::DEFAULT_MIN_LENGTH,
        string $alphabet = HashIdConfigInterface::DEFAULT_ALPHABET,
    ) {
        $this->defaultSalt = $salt ?? '';
        $this->defaultMinLength = $minLength;
        $this->defaultAlphabet = $alphabet;
    }

    /**
     * Create a hasher instance using dynamic constant fetch (PHP 8.3).
     *
     * @param string $type The hasher type (default, secure, custom)
     * @param array<string, mixed> $config Optional configuration overrides
     *
     * @return HasherInterface The created hasher instance
     */
    public function create(string $type = 'default', array $config = []): HasherInterface
    {
        // PHP 8.3 dynamic constant fetch
        $constantName = 'HASHER_' . \mb_strtoupper($type);

        // Check if constant exists
        if (!\defined(self::class . '::' . $constantName)) {
            throw new \InvalidArgumentException(\sprintf(
                'Unknown hasher type "%s". Available types: %s',
                $type,
                \implode(', ', \array_keys(self::HASHER_CONFIGS)),
            ));
        }

        // Dynamic constant fetch using PHP 8.3 syntax
        $hasherClass = self::{$constantName};

        // Merge configurations
        $hasherConfig = \array_merge(
            self::HASHER_CONFIGS[$type] ?? [],
            [
                'salt' => $this->defaultSalt,
                'min_length' => $this->defaultMinLength,
                'alphabet' => $this->defaultAlphabet,
            ],
            $config,
        );

        // Special handling for secure hasher
        if ($type === 'secure' && empty($hasherConfig['salt'])) {
            $hasherConfig['salt'] = $this->generateSecureSalt();
        }

        return new $hasherClass($hasherConfig);
    }

    /**
     * Create a converter instance with the specified hasher type.
     *
     * @param string $type The hasher type
     * @param array<string, mixed> $config Optional configuration
     */
    public function createConverter(string $type = 'default', array $config = []): ConverterInterface
    {
        $hasherConfig = \array_merge(
            self::HASHER_CONFIGS[$type] ?? [],
            [
                'salt' => $this->defaultSalt,
                'min_length' => $this->defaultMinLength,
                'alphabet' => $this->defaultAlphabet,
            ],
            $config,
        );

        $hashids = new Hashids(
            $hasherConfig['salt'],
            $hasherConfig['min_length'],
            $hasherConfig['alphabet'],
        );

        return new HashidsConverter($hashids);
    }

    /**
     * Get available hasher types using reflection on constants.
     * Uses caching to avoid repeated reflection operations for performance.
     *
     * @return array<string> List of available hasher types
     */
    public function getAvailableTypes(): array
    {
        // Return cached types if available
        if (self::$availableTypesCache !== null) {
            return self::$availableTypesCache;
        }
        
        // Perform reflection once and cache the results
        $reflection = new \ReflectionClass(self::class);
        $constants = $reflection->getConstants();

        $types = [];
        foreach ($constants as $name => $value) {
            if (\str_starts_with($name, 'HASHER_')) {
                $types[] = \mb_strtolower(\mb_substr($name, 7));
            }
        }

        // Cache the results for future calls
        self::$availableTypesCache = $types;
        
        return $types;
    }
    
    /**
     * Clear the available types cache.
     * Useful for testing or when hasher types are dynamically modified.
     */
    public static function clearCache(): void
    {
        self::$availableTypesCache = null;
    }

    /**
     * Generate a secure salt for the secure hasher.
     *
     * @return string A cryptographically secure salt
     */
    private function generateSecureSalt(): string
    {
        return \bin2hex(\random_bytes(32));
    }
}

/**
 * Interface for hasher implementations.
 */
interface HasherInterface
{
    public function __construct(array $config);
    public function encode($value): string;
    public function decode(string $hash);
}

/**
 * Default hasher implementation.
 */
class DefaultHasher implements HasherInterface
{
    private Hashids $hashids;

    public function __construct(array $config)
    {
        $this->hashids = new Hashids(
            $config['salt'] ?? '',
            $config['min_length'] ?? 10,
            $config['alphabet'] ?? HashIdConfigInterface::DEFAULT_ALPHABET,
        );
    }

    public function encode($value): string
    {
        if (!\is_numeric($value)) {
            return (string) $value;
        }

        return $this->hashids->encode((int) $value);
    }

    public function decode(string $hash)
    {
        $decoded = $this->hashids->decode($hash);

        return !empty($decoded) ? $decoded[0] : $hash;
    }
}

/**
 * Secure hasher with enhanced security features.
 */
class SecureHasher implements HasherInterface
{
    private Hashids $hashids;
    private string $salt;

    public function __construct(array $config)
    {
        $this->salt = $config['salt'] ?? \bin2hex(\random_bytes(32));
        $this->hashids = new Hashids(
            $this->salt,
            $config['min_length'] ?? 20,
            $config['alphabet'] ?? 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*',
        );
    }

    public function encode($value): string
    {
        if (!\is_numeric($value)) {
            return (string) $value;
        }

        // Add additional entropy for secure hashing
        $timestamp = \time();
        $encoded = $this->hashids->encode((int) $value, $timestamp);

        return $encoded;
    }

    public function decode(string $hash)
    {
        $decoded = $this->hashids->decode($hash);

        // Return first element (original value), ignore timestamp
        return !empty($decoded) ? $decoded[0] : $hash;
    }
}

/**
 * Custom hasher for user-defined implementations.
 */
class CustomHasher implements HasherInterface
{
    private Hashids $hashids;

    public function __construct(array $config)
    {
        $this->hashids = new Hashids(
            $config['salt'] ?? '',
            $config['min_length'] ?? 15,
            $config['alphabet'] ?? HashIdConfigInterface::DEFAULT_ALPHABET,
        );
    }

    public function encode($value): string
    {
        if (!\is_numeric($value)) {
            return (string) $value;
        }

        return $this->hashids->encode((int) $value);
    }

    public function decode(string $hash)
    {
        $decoded = $this->hashids->decode($hash);

        return !empty($decoded) ? $decoded[0] : $hash;
    }
}
