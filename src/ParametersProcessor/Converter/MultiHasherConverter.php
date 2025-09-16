<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\ParametersProcessor\Converter;

use Pgs\HashIdBundle\Service\HasherRegistry;

/**
 * Converter that supports multiple hashers through HasherRegistry.
 * 
 * This converter can encode/decode using different hashers based on configuration.
 * Uses PHP 8.3 readonly properties for better performance and immutability.
 * 
 * @since 4.0.0
 */
readonly class MultiHasherConverter implements ConverterInterface
{
    /**
     * @var string Current hasher context
     */
    private string $currentHasher;
    
    /**
     * Constructor with PHP 8.3 constructor property promotion.
     * 
     * @param HasherRegistry $hasherRegistry
     * @param string $currentHasher Default hasher to use
     */
    public function __construct(
        private HasherRegistry $hasherRegistry,
        string $currentHasher = 'default'
    ) {
        $this->currentHasher = $currentHasher;
    }
    
    /**
     * Set the hasher to use for subsequent operations.
     * Creates a new instance due to readonly properties.
     * 
     * @param string $hasherName
     * @return self
     */
    public function withHasher(string $hasherName): self
    {
        // Create new instance with different hasher (readonly class pattern)
        return new self($this->hasherRegistry, $hasherName);
    }
    
    /**
     * Encode a value using the current hasher.
     * Leverages HasherRegistry's internal caching for performance.
     * 
     * @param mixed $value
     * @return string
     */
    public function encode(mixed $value): string
    {
        // HasherRegistry caches converter instances internally
        $converter = $this->hasherRegistry->getConverter($this->currentHasher);
        return $converter->encode($value);
    }
    
    /**
     * Decode a value using the current hasher.
     * Leverages HasherRegistry's internal caching for performance.
     * 
     * @param string $value
     * @return mixed
     */
    public function decode(string $value): mixed
    {
        // HasherRegistry caches converter instances internally
        $converter = $this->hasherRegistry->getConverter($this->currentHasher);
        return $converter->decode($value);
    }
    
    /**
     * Get the current hasher name.
     * 
     * @return string
     */
    public function getCurrentHasher(): string
    {
        return $this->currentHasher;
    }
}