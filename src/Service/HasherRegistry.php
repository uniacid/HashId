<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Service;

use Hashids\Hashids;
use Hashids\HashidsInterface;
use Pgs\HashIdBundle\Config\HashIdConfigInterface;
use Pgs\HashIdBundle\Exception\HasherNotFoundException;
use Pgs\HashIdBundle\Exception\HashIdException;
use Pgs\HashIdBundle\Exception\HashIdError;
use Pgs\HashIdBundle\ParametersProcessor\Converter\ConverterInterface;
use Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter;

/**
 * Registry for managing multiple hasher instances.
 * 
 * This service manages multiple named hasher configurations,
 * allowing different security contexts for different use cases.
 * 
 * @since 4.0.0
 */
class HasherRegistry
{
    /**
     * Minimum required alphabet length for security.
     */
    private const MIN_ALPHABET_LENGTH = 16;
    
    /**
     * Maximum hasher name length.
     */
    private const MAX_HASHER_NAME_LENGTH = 50;
    
    /**
     * Pattern for valid hasher names.
     */
    private const HASHER_NAME_PATTERN = '/^[a-zA-Z0-9_\-\.]+$/';
    
    /**
     * @var array<string, array<string, mixed>> Hasher configurations
     */
    private array $configurations = [];
    
    /**
     * @var array<string, HashidsInterface> Cached Hashids instances
     */
    private array $hashidsCache = [];
    
    /**
     * @var array<string, ConverterInterface> Cached converter instances
     */
    private array $converterCache = [];
    
    /**
     * Constructor initializes with default hasher.
     */
    public function __construct()
    {
        // Always register a default hasher
        $this->registerHasher('default', [
            'salt' => HashIdConfigInterface::DEFAULT_SALT,
            'min_hash_length' => HashIdConfigInterface::DEFAULT_MIN_LENGTH,
            'alphabet' => HashIdConfigInterface::DEFAULT_ALPHABET,
        ]);
    }
    
    /**
     * Register a hasher configuration.
     * 
     * @param string $name The hasher name
     * @param array<string, mixed> $config The hasher configuration
     * 
     * @throws \InvalidArgumentException If configuration is invalid
     */
    public function registerHasher(string $name, array $config): void
    {
        $this->validateHasherName($name);
        $this->validateConfiguration($config);
        
        // Process environment variables in configuration
        $config = $this->processEnvironmentVariables($config);
        
        // Set defaults for missing values
        $config = array_merge([
            'salt' => HashIdConfigInterface::DEFAULT_SALT,
            'min_hash_length' => HashIdConfigInterface::DEFAULT_MIN_LENGTH,
            'alphabet' => HashIdConfigInterface::DEFAULT_ALPHABET,
        ], $config);
        
        $this->configurations[$name] = $config;
        
        // Clear caches for this hasher to force recreation
        unset($this->hashidsCache[$name]);
        unset($this->converterCache[$name]);
    }
    
    /**
     * Register multiple hashers at once.
     * 
     * @param array<string, array<string, mixed>> $hashers
     */
    public function registerHashers(array $hashers): void
    {
        foreach ($hashers as $name => $config) {
            $this->registerHasher($name, $config);
        }
    }
    
    /**
     * Get a converter instance for the specified hasher.
     * 
     * @param string $name The hasher name
     * @return ConverterInterface
     * 
     * @throws HasherNotFoundException If hasher doesn't exist
     */
    public function getConverter(string $name = 'default'): ConverterInterface
    {
        if (!isset($this->configurations[$name])) {
            throw HashIdException::hasherNotFound($name, array_keys($this->configurations));
        }
        
        // Return cached converter if available
        if (isset($this->converterCache[$name])) {
            return $this->converterCache[$name];
        }
        
        // Create and cache new converter
        $hashids = $this->getHashids($name);
        $converter = new HashidsConverter($hashids);
        $this->converterCache[$name] = $converter;
        
        return $converter;
    }
    
    /**
     * Get a Hashids instance for the specified hasher.
     * 
     * @param string $name The hasher name
     * @return HashidsInterface
     * 
     * @throws HasherNotFoundException If hasher doesn't exist
     */
    public function getHashids(string $name = 'default'): HashidsInterface
    {
        if (!isset($this->configurations[$name])) {
            throw HashIdException::hasherNotFound($name, array_keys($this->configurations));
        }
        
        // Return cached instance if available
        if (isset($this->hashidsCache[$name])) {
            return $this->hashidsCache[$name];
        }
        
        // Create and cache new Hashids instance
        $config = $this->configurations[$name];
        $hashids = new Hashids(
            $config['salt'],
            $config['min_hash_length'],
            $config['alphabet']
        );
        $this->hashidsCache[$name] = $hashids;
        
        return $hashids;
    }
    
    /**
     * Check if a hasher exists.
     * 
     * @param string $name The hasher name
     * @return bool
     */
    public function hasHasher(string $name): bool
    {
        return isset($this->configurations[$name]);
    }
    
    /**
     * Get all registered hasher names.
     * 
     * @return array<string>
     */
    public function getHasherNames(): array
    {
        return array_keys($this->configurations);
    }
    
    /**
     * Get configuration for a specific hasher.
     * 
     * @param string $name The hasher name
     * @return array<string, mixed>|null
     */
    public function getHasherConfiguration(string $name): ?array
    {
        return $this->configurations[$name] ?? null;
    }
    
    /**
     * Validate hasher name.
     * 
     * @param string $name
     * @throws \InvalidArgumentException
     */
    private function validateHasherName(string $name): void
    {
        if (empty($name)) {
            throw HashIdException::invalidParameter('hasher_name', 'Hasher name cannot be empty');
        }
        
        if (strlen($name) > self::MAX_HASHER_NAME_LENGTH) {
            throw HashIdException::invalidParameter(
                'hasher_name',
                sprintf('Name too long (max %d characters)', self::MAX_HASHER_NAME_LENGTH)
            );
        }
        
        if (!preg_match(self::HASHER_NAME_PATTERN, $name)) {
            throw HashIdException::invalidParameter(
                'hasher_name',
                sprintf('Invalid name "%s". Names can only contain letters, numbers, underscores, hyphens, and dots.', $name)
            );
        }
    }
    
    /**
     * Validate hasher configuration.
     * 
     * @param array<string, mixed> $config
     * @throws \InvalidArgumentException
     */
    private function validateConfiguration(array $config): void
    {
        // Validate min_hash_length if provided
        if (isset($config['min_hash_length'])) {
            $minLength = $config['min_hash_length'];
            if (!is_int($minLength) || $minLength < 0) {
                throw HashIdException::configurationError(
                    'min_hash_length',
                    'Must be non-negative integer'
                );
            }
            if ($minLength > HashIdConfigInterface::MAX_LENGTH) {
                throw HashIdException::configurationError(
                    'min_hash_length',
                    sprintf('Cannot exceed %d', HashIdConfigInterface::MAX_LENGTH)
                );
            }
        }
        
        // Validate alphabet if provided
        if (isset($config['alphabet'])) {
            $alphabet = $config['alphabet'];
            if (!is_string($alphabet)) {
                throw HashIdException::configurationError('alphabet', 'Must be a string');
            }
            
            $uniqueChars = count(array_unique(str_split($alphabet)));
            if ($uniqueChars < self::MIN_ALPHABET_LENGTH) {
                throw HashIdException::configurationError(
                    'alphabet',
                    sprintf('Must contain at least %d unique characters, got %d', self::MIN_ALPHABET_LENGTH, $uniqueChars)
                );
            }
        }
        
        // Validate salt if provided
        if (isset($config['salt']) && !is_string($config['salt'])) {
            throw HashIdException::configurationError('salt', 'Must be a string');
        }
    }
    
    /**
     * Process environment variables in configuration.
     * 
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function processEnvironmentVariables(array $config): array
    {
        foreach ($config as $key => $value) {
            if (is_string($value) && preg_match('/^%env\(([^)]+)\)%$/', $value, $matches)) {
                $envVar = $matches[1];
                
                // Handle typed environment variables like env(int:VAR_NAME)
                if (strpos($envVar, ':') !== false) {
                    [$type, $varName] = explode(':', $envVar, 2);
                    $envValue = $_ENV[$varName] ?? $_SERVER[$varName] ?? null;
                    
                    if ($envValue !== null) {
                        $config[$key] = $this->castEnvironmentValue($envValue, $type);
                    }
                } else {
                    // Simple environment variable
                    $config[$key] = $_ENV[$envVar] ?? $_SERVER[$envVar] ?? $value;
                }
            }
        }
        
        return $config;
    }
    
    /**
     * Cast environment value to specified type.
     * 
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    private function castEnvironmentValue($value, string $type)
    {
        return match ($type) {
            'int' => (int) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'float' => (float) $value,
            default => (string) $value,
        };
    }
    
    /**
     * Clear all caches.
     */
    public function clearCaches(): void
    {
        $this->hashidsCache = [];
        $this->converterCache = [];
    }
}