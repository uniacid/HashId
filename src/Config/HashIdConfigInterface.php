<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Config;

/**
 * HashId configuration interface with typed constants (PHP 8.3+).
 *
 * Defines typed configuration constants for the HashId bundle,
 * providing type safety and better IDE support.
 *
 * @since 4.0.0
 */
interface HashIdConfigInterface
{
    /**
     * Minimum hash length for generated IDs.
     */
    public const int MIN_LENGTH = 10;

    /**
     * Maximum hash length for generated IDs.
     */
    public const int MAX_LENGTH = 255;

    /**
     * Default minimum hash length if not configured.
     */
    public const int DEFAULT_MIN_LENGTH = 10;

    /**
     * Default alphabet for hash generation.
     * Uses alphanumeric characters for URL-safe hashes.
     */
    public const string DEFAULT_ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

    /**
     * Default salt value (empty for backward compatibility).
     * Should be overridden in production with a secure value.
     */
    public const string DEFAULT_SALT = '';

    /**
     * Bundle configuration root name.
     */
    public const string ROOT_NAME = 'pgs_hash_id';

    /**
     * Configuration node names for DI configuration.
     */
    public const string NODE_CONVERTER = 'converter';
    public const string NODE_CONVERTER_HASHIDS = 'hashids';
    public const string NODE_CONVERTER_HASHIDS_SALT = 'salt';
    public const string NODE_CONVERTER_HASHIDS_MIN_HASH_LENGTH = 'min_hash_length';
    public const string NODE_CONVERTER_HASHIDS_ALPHABET = 'alphabet';

    /**
     * Hasher type constants for dynamic hasher selection.
     */
    public const string HASHER_DEFAULT = 'default';
    public const string HASHER_SECURE = 'secure';
    public const string HASHER_CUSTOM = 'custom';

    /**
     * Maximum recursion depth for parameter processing.
     */
    public const int MAX_RECURSION_DEPTH = 10;

    /**
     * Default cache TTL for processed hashes (in seconds).
     */
    public const int DEFAULT_CACHE_TTL = 3600;
    
    /**
     * Performance optimization constants (PHP 8.3).
     */
    
    /**
     * Maximum cache size for encoded values.
     */
    public const int MAX_ENCODE_CACHE_SIZE = 1000;
    
    /**
     * Maximum cache size for decoded values.
     */
    public const int MAX_DECODE_CACHE_SIZE = 1000;
    
    /**
     * Batch processing size for bulk operations.
     */
    public const int BATCH_SIZE = 100;
    
    /**
     * Memory limit threshold for cache cleanup (in bytes).
     * When memory usage exceeds this, caches are cleared.
     */
    public const int MEMORY_LIMIT_THRESHOLD = 134217728; // 128MB
    
    /**
     * Cache warmup size for frequently used values.
     */
    public const int CACHE_WARMUP_SIZE = 50;
    
    /**
     * Performance monitoring interval (in seconds).
     */
    public const int PERFORMANCE_MONITOR_INTERVAL = 300; // 5 minutes
}
