<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Annotation;

/**
 * @Annotation
 *
 * @Target({"METHOD"})
 */
readonly class Hash
{
    /** @var array<int, string> */
    private array $parameters;
    
    /** @var string The hasher to use for encoding/decoding */
    private string $hasher;

    /** @param array<string, mixed>|array<int, string> $parameters */
    public function __construct(array $parameters)
    {
        // Handle annotation format
        if (isset($parameters['value'])) {
            if (\is_array($parameters['value'])) {
                $this->parameters = array_values($parameters['value']);
            } else {
                $this->parameters = [$parameters['value']];
            }
            $this->hasher = isset($parameters['hasher']) ? (string) $parameters['hasher'] : 'default';
        } else {
            // Handle direct array format
            $this->parameters = array_values(array_filter($parameters, 'is_string'));
            $this->hasher = 'default';
        }
        
        // Normalize hasher name
        $this->hasher = trim($this->hasher);
        if (empty($this->hasher)) {
            $this->hasher = 'default';
        }
    }

    /** @return array<int, string> */
    public function getParameters(): array
    {
        return $this->parameters;
    }
    
    /** @return string */
    public function getHasher(): string
    {
        return $this->hasher;
    }
}
