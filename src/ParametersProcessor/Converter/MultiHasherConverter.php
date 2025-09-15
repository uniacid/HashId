<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\ParametersProcessor\Converter;

use Pgs\HashIdBundle\Service\HasherRegistry;

/**
 * Converter that supports multiple hashers through HasherRegistry.
 * 
 * This converter can encode/decode using different hashers based on configuration.
 * 
 * @since 4.0.0
 */
class MultiHasherConverter implements ConverterInterface
{
    /**
     * @var HasherRegistry
     */
    private HasherRegistry $hasherRegistry;
    
    /**
     * @var string Current hasher context
     */
    private string $currentHasher = 'default';
    
    /**
     * Constructor.
     * 
     * @param HasherRegistry $hasherRegistry
     */
    public function __construct(HasherRegistry $hasherRegistry)
    {
        $this->hasherRegistry = $hasherRegistry;
    }
    
    /**
     * Set the hasher to use for subsequent operations.
     * 
     * @param string $hasherName
     * @return self
     */
    public function withHasher(string $hasherName): self
    {
        $clone = clone $this;
        $clone->currentHasher = $hasherName;
        return $clone;
    }
    
    /**
     * Encode a value using the current hasher.
     * 
     * @param mixed $value
     * @return string
     */
    public function encode(mixed $value): string
    {
        $converter = $this->hasherRegistry->getConverter($this->currentHasher);
        return $converter->encode($value);
    }
    
    /**
     * Decode a value using the current hasher.
     * 
     * @param string $value
     * @return mixed
     */
    public function decode(string $value): mixed
    {
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