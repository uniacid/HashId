<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Service;

use InvalidArgumentException;
use ReflectionClass;
use Hashids\Hashids;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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

    private readonly ?string $defaultSalt;
    private readonly int $defaultMinLength;
    private readonly string $defaultAlphabet;
    
    /**
     * @var array<string>|null Cached available types to avoid repeated reflection
     */
    private static ?array $availableTypesCache = null;

    /**
     * @var array<string, HasherInterface> Cached hasher instances
     */
    private array $instanceCache = [];

    /**
     * @var array<string, float> Cache access timestamps for LRU eviction
     */
    private array $cacheAccessTimes = [];

    /**
     * @var array<string, array<string, mixed>> Lazy configuration cache
     */
    private array $lazyConfigCache = [];

    /**
     * Cache hit/miss metrics.
     */
    private int $cacheHits = 0;
    private int $cacheMisses = 0;
    private int $cacheEvictions = 0;

    /**
     * Default maximum number of cached hasher instances.
     */
    private const DEFAULT_MAX_CACHE_SIZE = 50;
    
    /**
     * Minimum required alphabet length for security.
     */
    private const MIN_ALPHABET_LENGTH = 16;
    
    /**
     * Maximum cache size for this instance.
     */
    private readonly int $maxCacheSize;

    /**
     * Logger for debugging and performance monitoring.
     */
    private readonly LoggerInterface $logger;

    public function __construct(
        ?string $salt = null,
        int $minLength = HashIdConfigInterface::DEFAULT_MIN_LENGTH,
        string $alphabet = HashIdConfigInterface::DEFAULT_ALPHABET,
        int $maxCacheSize = self::DEFAULT_MAX_CACHE_SIZE,
        ?LoggerInterface $logger = null,
    ) {
        // Validate min_length
        if ($minLength < 0) {
            throw new InvalidArgumentException(
                \sprintf('Minimum length must be non-negative, got %d', $minLength)
            );
        }
        
        // Validate alphabet
        $uniqueChars = \count(\array_unique(\str_split($alphabet)));
        if ($uniqueChars < self::MIN_ALPHABET_LENGTH) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Alphabet must contain at least %d unique characters, got %d',
                    self::MIN_ALPHABET_LENGTH,
                    $uniqueChars
                )
            );
        }
        
        // Validate cache size
        if ($maxCacheSize < 1) {
            throw new InvalidArgumentException(
                \sprintf('Max cache size must be positive, got %d', $maxCacheSize)
            );
        }
        
        $this->defaultSalt = $salt ?? '';
        $this->defaultMinLength = $minLength;
        $this->defaultAlphabet = $alphabet;
        $this->maxCacheSize = $maxCacheSize;
        $this->logger = $logger ?? new NullLogger();

        $this->logger->debug('HasherFactory initialized', [
            'salt_length' => \strlen($this->defaultSalt),
            'min_length' => $this->defaultMinLength,
            'alphabet_length' => \strlen($this->defaultAlphabet),
            'max_cache_size' => $this->maxCacheSize,
        ]);
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
        $startTime = \microtime(true);

        $this->logger->debug('Creating hasher', [
            'type' => $type,
            'config_keys' => \array_keys($config),
            'cache_size' => \count($this->instanceCache),
        ]);

        // Security: Validate type against whitelist to prevent injection
        $allowedTypes = ['default', 'secure', 'custom'];
        if (!\in_array($type, $allowedTypes, true)) {
            $this->logger->error('Invalid hasher type requested', [
                'type' => $type,
                'allowed_types' => $allowedTypes,
            ]);
            throw new InvalidArgumentException(\sprintf(
                'Unknown hasher type "%s". Available types: %s',
                $type,
                \implode(', ', $allowedTypes),
            ));
        }

        // Use switch statement for safe constant access
        $hasherClass = match($type) {
            'default' => self::HASHER_DEFAULT,
            'secure' => self::HASHER_SECURE,
            'custom' => self::HASHER_CUSTOM,
            default => throw new InvalidArgumentException("Invalid hasher type: {$type}")
        };

        // Merge configurations - defaults first, then type-specific, then user config
        $hasherConfig = \array_merge(
            [
                'salt' => $this->defaultSalt,
                'min_length' => $this->defaultMinLength,
                'alphabet' => $this->defaultAlphabet,
            ],
            self::HASHER_CONFIGS[$type] ?? [],
            $config,
        );
        
        // Validate merged configuration
        if (isset($hasherConfig['min_length']) && $hasherConfig['min_length'] < 0) {
            throw new InvalidArgumentException(
                \sprintf('Minimum length must be non-negative, got %d', $hasherConfig['min_length'])
            );
        }
        
        if (isset($hasherConfig['alphabet'])) {
            $uniqueChars = \count(\array_unique(\str_split($hasherConfig['alphabet'])));
            if ($uniqueChars < self::MIN_ALPHABET_LENGTH) {
                throw new InvalidArgumentException(
                    \sprintf(
                        'Alphabet must contain at least %d unique characters, got %d',
                        self::MIN_ALPHABET_LENGTH,
                        $uniqueChars
                    )
                );
            }
        }

        // Special handling for secure hasher
        if ($type === 'secure' && empty($hasherConfig['salt'])) {
            $hasherConfig['salt'] = $this->generateSecureSalt();
        }

        // Generate cache key for this configuration
        $cacheKey = $this->generateCacheKey($type, $hasherConfig);
        
        // Check instance cache for already created instances
        if (isset($this->instanceCache[$cacheKey])) {
            // Cache hit - update access time for LRU
            $this->cacheAccessTimes[$cacheKey] = \microtime(true);
            ++$this->cacheHits;

            $duration = \microtime(true) - $startTime;
            $this->logger->debug('Cache hit', [
                'type' => $type,
                'cache_key' => \substr($cacheKey, 0, 16) . '...',
                'duration_ms' => \round($duration * 1000, 3),
                'cache_hit_rate' => $this->getCacheHitRate(),
            ]);

            return $this->instanceCache[$cacheKey];
        }

        // Cache miss
        ++$this->cacheMisses;

        $this->logger->debug('Cache miss', [
            'type' => $type,
            'cache_key' => \substr($cacheKey, 0, 16) . '...',
            'cache_size' => \count($this->instanceCache),
            'cache_hit_rate' => $this->getCacheHitRate(),
        ]);
        
        // Check if we have lazy config stored but not instantiated yet
        if (isset($this->lazyConfigCache[$cacheKey])) {
            return $this->createLazyInstance($cacheKey, $hasherClass, $this->lazyConfigCache[$cacheKey], $type, $startTime);
        }

        // Store configuration for lazy loading
        $this->lazyConfigCache[$cacheKey] = $hasherConfig;

        // Create and cache the instance
        return $this->createLazyInstance($cacheKey, $hasherClass, $hasherConfig, $type, $startTime);
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
        $reflection = new ReflectionClass(self::class);
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
     * Generate a cache key for hasher configuration.
     *
     * @param string $type The hasher type
     * @param array<string, mixed> $config The configuration
     * @return string The cache key
     */
    private function generateCacheKey(string $type, array $config): string
    {
        // Use json_encode for better performance than serialize
        // Sort keys to ensure consistent hashing
        \ksort($config);
        return $type . ':' . \md5(\json_encode($config));
    }

    /**
     * Cache a hasher instance with LRU eviction.
     *
     * @param string $key The cache key
     * @param HasherInterface $hasher The hasher instance
     */
    private function cacheInstance(string $key, HasherInterface $hasher): void
    {
        // If cache is full, remove the least recently used entry (LRU)
        if (\count($this->instanceCache) >= $this->maxCacheSize) {
            $this->evictLeastRecentlyUsed();
        }

        // Add new entry with current timestamp
        $this->instanceCache[$key] = $hasher;
        $this->cacheAccessTimes[$key] = \microtime(true);
    }

    /**
     * Evict the least recently used cache entry.
     */
    private function evictLeastRecentlyUsed(): void
    {
        if (empty($this->cacheAccessTimes)) {
            $this->logger->warning('Attempted to evict from empty cache');
            return;
        }

        // Find the entry with the oldest access time
        $oldestKey = \array_key_first($this->cacheAccessTimes);
        $oldestTime = $this->cacheAccessTimes[$oldestKey];

        foreach ($this->cacheAccessTimes as $key => $accessTime) {
            if ($accessTime < $oldestTime) {
                $oldestKey = $key;
                $oldestTime = $accessTime;
            }
        }

        // Calculate age of evicted entry
        $ageSeconds = \microtime(true) - $oldestTime;

        $this->logger->debug('Evicting LRU cache entry', [
            'evicted_key' => \substr($oldestKey, 0, 16) . '...',
            'age_seconds' => \round($ageSeconds, 3),
            'cache_size_before' => \count($this->instanceCache),
            'total_evictions' => $this->cacheEvictions + 1,
        ]);

        // Remove the least recently used entry
        unset($this->instanceCache[$oldestKey], $this->cacheAccessTimes[$oldestKey]);
        ++$this->cacheEvictions;
    }

    /**
     * Clear the instance cache.
     */
    public function clearInstanceCache(): void
    {
        $clearedEntries = \count($this->instanceCache);

        $this->logger->info('Clearing instance cache', [
            'cleared_entries' => $clearedEntries,
            'cache_hit_rate' => $this->getCacheHitRate(),
            'total_evictions' => $this->cacheEvictions,
        ]);

        $this->instanceCache = [];
        $this->cacheAccessTimes = [];
        $this->lazyConfigCache = [];
    }

    /**
     * Get cache performance statistics.
     *
     * @return array<string, mixed> Cache metrics
     */
    public function getCacheStatistics(): array
    {
        $totalRequests = $this->cacheHits + $this->cacheMisses;
        $hitRate = $totalRequests > 0 ? \round(($this->cacheHits / $totalRequests) * 100, 2) : 0.0;

        return [
            'cache_hits' => $this->cacheHits,
            'cache_misses' => $this->cacheMisses,
            'cache_evictions' => $this->cacheEvictions,
            'hit_rate_percentage' => $hitRate,
            'current_cache_size' => \count($this->instanceCache),
            'max_cache_size' => $this->maxCacheSize,
            'cache_usage_percentage' => \round((\count($this->instanceCache) / $this->maxCacheSize) * 100, 1),
        ];
    }

    /**
     * Reset cache performance counters.
     */
    public function resetCacheStatistics(): void
    {
        $this->cacheHits = 0;
        $this->cacheMisses = 0;
        $this->cacheEvictions = 0;
    }

    /**
     * Get current cache hit rate as a percentage.
     *
     * @return float Hit rate percentage
     */
    private function getCacheHitRate(): float
    {
        $totalRequests = $this->cacheHits + $this->cacheMisses;
        return $totalRequests > 0 ? \round(($this->cacheHits / $totalRequests) * 100, 2) : 0.0;
    }
    
    /**
     * Create a lazy instance and cache it.
     *
     * @param string $cacheKey The cache key
     * @param class-string<HasherInterface> $hasherClass The hasher class
     * @param array<string, mixed> $config The configuration
     * @param string $type The hasher type for logging
     * @param float $startTime Creation start time for performance logging
     * @return HasherInterface
     */
    private function createLazyInstance(string $cacheKey, string $hasherClass, array $config, string $type, float $startTime): HasherInterface
    {
        $creationStart = \microtime(true);

        // Create the instance only when needed
        $hasher = new $hasherClass($config);

        $creationTime = \microtime(true) - $creationStart;
        $totalTime = \microtime(true) - $startTime;

        $this->logger->debug('Created new hasher instance', [
            'type' => $type,
            'class' => \basename($hasherClass),
            'creation_time_ms' => \round($creationTime * 1000, 3),
            'total_time_ms' => \round($totalTime * 1000, 3),
            'cache_key' => \substr($cacheKey, 0, 16) . '...',
        ]);

        // Cache the instance with LRU eviction
        $this->cacheInstance($cacheKey, $hasher);

        $this->logger->debug('Hasher creation complete', [
            'type' => $type,
            'cache_size' => \count($this->instanceCache),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'total_requests' => $this->cacheHits + $this->cacheMisses,
        ]);

        return $hasher;
    }
    
    /**
     * Pre-configure a hasher type for lazy loading without instantiation.
     * 
     * @param string $type The hasher type
     * @param array<string, mixed> $config Configuration overrides
     * @return string The cache key for this configuration
     */
    public function preloadConfiguration(string $type = 'default', array $config = []): string
    {
        // Validate type
        $allowedTypes = ['default', 'secure', 'custom'];
        if (!\in_array($type, $allowedTypes, true)) {
            throw new InvalidArgumentException(\sprintf(
                'Unknown hasher type "%s". Available types: %s',
                $type,
                \implode(', ', $allowedTypes),
            ));
        }
        
        // Merge configurations - defaults first, then type-specific, then user config
        $hasherConfig = \array_merge(
            [
                'salt' => $this->defaultSalt,
                'min_length' => $this->defaultMinLength,
                'alphabet' => $this->defaultAlphabet,
            ],
            self::HASHER_CONFIGS[$type] ?? [],
            $config,
        );
        
        // Generate and store cache key
        $cacheKey = $this->generateCacheKey($type, $hasherConfig);
        $this->lazyConfigCache[$cacheKey] = $hasherConfig;
        
        return $cacheKey;
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
    private readonly Hashids $hashids;

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
    private readonly Hashids $hashids;
    private readonly string $salt;

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
    private readonly Hashids $hashids;

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
