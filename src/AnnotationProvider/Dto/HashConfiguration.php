<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\AnnotationProvider\Dto;

use Pgs\HashIdBundle\Exception\HashIdException;

/**
 * Data Transfer Object for Hash configuration.
 * 
 * This class replaces the anonymous class in AttributeProvider,
 * providing proper type safety and reusability.
 * 
 * @since 4.0.0
 */
final class HashConfiguration
{
    /**
     * @var array<int, string> The parameter names to be hashed
     */
    private array $parameters;

    /**
     * @param array<int, string> $parameters The parameter names to be hashed
     */
    public function __construct(array $parameters)
    {
        // Validate and normalize parameters
        $this->parameters = [];
        foreach ($parameters as $parameter) {
            if (!\is_string($parameter)) {
<<<<<<< HEAD
                throw HashIdException::invalidParameter(
                    'parameter',
                    \sprintf('Must be a string, got %s', \gettype($parameter))
=======
                throw new InvalidArgumentException(
                    \sprintf('Parameter must be a string, got %s', \gettype($parameter))
>>>>>>> 9f431ad (refactor: Improve type handling and exception management across the codebase)
                );
            }
            
            // Validate parameter name
            if (!$this->isValidParameterName($parameter)) {
<<<<<<< HEAD
                throw HashIdException::invalidParameter(
                    $parameter,
                    'Invalid parameter name format'
=======
                throw new InvalidArgumentException(
                    \sprintf('Invalid parameter name: "%s"', $parameter)
>>>>>>> 9f431ad (refactor: Improve type handling and exception management across the codebase)
                );
            }
            
            $this->parameters[] = $parameter;
        }
        
        // Remove duplicates while preserving order
        $this->parameters = \array_values(\array_unique($this->parameters));
    }

    /**
     * Get the parameter names to be hashed.
     * 
     * @return array<int, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
    
    /**
     * Check if a parameter name is valid.
     * 
     * @param string $name The parameter name to validate
     * @return bool True if valid, false otherwise
     */
    private function isValidParameterName(string $name): bool
    {
        // Parameter name must:
        // - Not be empty
        // - Start with a letter or underscore
        // - Contain only letters, numbers, and underscores
        // - Be reasonably short (max 100 characters)
        
        if (empty($name) || \strlen($name) > 100) {
            return false;
        }
        
        return \preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name) === 1;
    }
    
    /**
     * Check if this configuration has any parameters.
     * 
     * @return bool
     */
    public function hasParameters(): bool
    {
        return \count($this->parameters) > 0;
    }
    
    /**
     * Check if a specific parameter is configured for hashing.
     * 
     * @param string $parameter The parameter name to check
     * @return bool
     */
    public function hasParameter(string $parameter): bool
    {
        return \in_array($parameter, $this->parameters, true);
    }
}