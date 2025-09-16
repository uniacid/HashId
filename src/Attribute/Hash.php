<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Attribute;

use Pgs\HashIdBundle\Exception\HashIdException;

/**
 * PHP 8 Attribute for marking route parameters to be encoded/decoded.
 *
 * This is the modern replacement for the @Hash annotation.
 * Both are supported in v4.x for backward compatibility.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Hash
{
    /**
     * Maximum number of parameters allowed.
     */
    private const MAX_PARAMETERS = 20;

    /**
     * Pattern for valid parameter names.
     */
    private const PARAM_NAME_PATTERN = '/^[a-zA-Z0-9_]+$/';
    
    /**
     * Pattern for valid hasher names.
     */
    private const HASHER_NAME_PATTERN = '/^[a-zA-Z0-9_\-\.]+$/';
    
    /**
     * Maximum hasher name length.
     */
    private const MAX_HASHER_NAME_LENGTH = 50;

    /** @var array<string> */
    private array $parameters;
    
    /** @var string The hasher to use for encoding/decoding */
    private string $hasher;

    /**
     * @param string|array<string> $parameters Parameter name(s) to be encoded/decoded
     * @param string $hasher The hasher name to use (default: 'default')
     * @throws \InvalidArgumentException If parameters or hasher are invalid
     */
    public function __construct(string|array $parameters, string $hasher = 'default')
    {
        $this->parameters = \is_array($parameters) ? $parameters : [$parameters];
        $this->hasher = $this->validateAndNormalizeHasher($hasher);

        // Validate parameter count
        if (\count($this->parameters) > self::MAX_PARAMETERS) {
            throw HashIdException::invalidParameter(
                'parameters',
                \sprintf('Too many parameters specified (max %d, got %d)', self::MAX_PARAMETERS, \count($this->parameters))
            );
        }

        // Validate parameter names
        foreach ($this->parameters as $param) {
            if (!\preg_match(self::PARAM_NAME_PATTERN, $param)) {
                throw HashIdException::invalidParameter(
                    $param,
                    'Parameter names must contain only letters, numbers, and underscores'
                );
            }

            // Additional length check for parameter names
            if (\strlen($param) > 100) {
                throw HashIdException::invalidParameter(
                    $param,
                    'Parameter name too long (max 100 characters)'
                );
            }
        }
    }

    /**
     * Get the parameters that should be encoded/decoded.
     * 
     * @return array<string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
    
    /**
     * Get the hasher name to use for encoding/decoding.
     * 
     * @return string
     */
    public function getHasher(): string
    {
        return $this->hasher;
    }
    
    /**
     * Validate and normalize the hasher name.
     * 
     * @param string $hasher
     * @return string
     * @throws \InvalidArgumentException
     */
    private function validateAndNormalizeHasher(string $hasher): string
    {
        $hasher = trim($hasher);
        
        // Default to 'default' if empty
        if (empty($hasher)) {
            return 'default';
        }
        
        // Validate hasher name length
        if (strlen($hasher) > self::MAX_HASHER_NAME_LENGTH) {
            throw HashIdException::invalidParameter(
                'hasher',
                sprintf('Hasher name too long (max %d characters)', self::MAX_HASHER_NAME_LENGTH)
            );
        }
        
        // Validate hasher name pattern
        if (!preg_match(self::HASHER_NAME_PATTERN, $hasher)) {
            throw HashIdException::invalidParameter(
                'hasher',
                sprintf('Invalid hasher name "%s". Hasher names can only contain letters, numbers, underscores, hyphens, and dots.', $hasher)
            );
        }
        
        return $hasher;
    }
}
