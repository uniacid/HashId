<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\ParametersProcessor\Converter;

use Hashids\HashidsInterface;
use Pgs\HashIdBundle\Config\HashIdConfigInterface;

/**
 * Hashids converter with PHP 8.3 optimizations.
 * 
 * Uses readonly instance properties for immutability and better opcache optimization.
 * Static caches are maintained for performance across instances.
 * 
 * @since 4.0.0
 */
class HashidsConverter implements ConverterInterface
{
    /**
     * Cache for frequently encoded values to improve performance.
     * Static property for persistence across instances.
     * 
     * @var array<int, string>
     */
    private static array $encodeCache = [];
    
    /**
     * Cache for frequently decoded values to improve performance.
     * Static property for persistence across instances.
     * 
     * @var array<string, mixed>
     */
    private static array $decodeCache = [];
    
    /**
     * Constructor with readonly property for immutability.
     */
    public function __construct(
        private readonly HashidsInterface $hashids
    ) {
    }

    public function encode(mixed $value): string
    {
        // Check cache first for performance
        if (isset(self::$encodeCache[$value])) {
            return self::$encodeCache[$value];
        }
        
        $encoded = $this->hashids->encode($value);
        
        // Cache the result if under limit (using typed constant)
        if (count(self::$encodeCache) < HashIdConfigInterface::MAX_ENCODE_CACHE_SIZE) {
            self::$encodeCache[$value] = $encoded;
        }
        
        return $encoded;
    }

    public function decode(string $value): mixed
    {
        // Check cache first for performance
        if (isset(self::$decodeCache[$value])) {
            return self::$decodeCache[$value];
        }
        
        $result = $this->hashids->decode($value);
        $decoded = $result[0] ?? $value;
        
        // Cache the result if under limit (using typed constant)
        if (count(self::$decodeCache) < HashIdConfigInterface::MAX_DECODE_CACHE_SIZE) {
            self::$decodeCache[$value] = $decoded;
        }
        
        return $decoded;
    }
    
    /**
     * Clear all caches (useful for testing or memory management).
     */
    public static function clearCaches(): void
    {
        self::$encodeCache = [];
        self::$decodeCache = [];
    }
    
    /**
     * Get cache statistics for monitoring.
     * 
     * @return array{encode_size: int, decode_size: int}
     */
    public static function getCacheStats(): array
    {
        return [
            'encode_size' => isset(self::$encodeCache) ? count(self::$encodeCache) : 0,
            'decode_size' => isset(self::$decodeCache) ? count(self::$decodeCache) : 0,
        ];
    }
}
