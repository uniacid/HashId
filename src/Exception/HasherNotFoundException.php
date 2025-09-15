<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Exception;

/**
 * Exception thrown when a requested hasher is not found in the registry.
 * 
 * @since 4.0.0
 */
class HasherNotFoundException extends \RuntimeException
{
    /**
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Create exception for missing hasher.
     * 
     * @param string $hasherName
     * @return self
     */
    public static function forHasher(string $hasherName): self
    {
        return new self(sprintf(
            'Hasher "%s" not found. Available hashers can be configured in pgs_hash_id.hashers',
            $hasherName
        ));
    }
    
    /**
     * Create exception with list of available hashers.
     * 
     * @param string $hasherName
     * @param array<string> $availableHashers
     * @return self
     */
    public static function withAvailableHashers(string $hasherName, array $availableHashers): self
    {
        if (empty($availableHashers)) {
            return new self(sprintf(
                'Hasher "%s" not found. No hashers are configured.',
                $hasherName
            ));
        }
        
        return new self(sprintf(
            'Hasher "%s" not found. Available hashers: %s',
            $hasherName,
            implode(', ', $availableHashers)
        ));
    }
}