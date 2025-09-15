<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Exception;

/**
 * Base exception class for HashId bundle that uses HashIdError enum.
 * 
 * This modern exception class integrates with the HashIdError enum
 * to provide type-safe error handling with consistent error messages
 * and codes. Uses PHP 8.3 readonly properties for immutability.
 * 
 * @since 4.0.0
 */
class HashIdException extends \RuntimeException implements Exception
{
    private readonly HashIdError $error;
    private readonly array $context;
    
    /**
     * Create a new HashIdException.
     * 
     * @param HashIdError $error The error enum value
     * @param string $message Optional custom message (uses enum message if empty)
     * @param \Throwable|null $previous Previous exception for chaining
     * @param array<string, mixed> $context Additional error context
     */
    public function __construct(
        HashIdError $error,
        string $message = '',
        ?\Throwable $previous = null,
        array $context = []
    ) {
        $this->error = $error;
        $this->context = $context;
        
        // Use custom message if provided, otherwise use enum message
        $finalMessage = $message ?: $error->formatWithContext($context);
        
        parent::__construct($finalMessage, $error->getCode(), $previous);
    }
    
    /**
     * Get the error enum value.
     * 
     * @return HashIdError
     */
    public function getError(): HashIdError
    {
        return $this->error;
    }
    
    /**
     * Get the error context.
     * 
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
    
    /**
     * Get the error severity level.
     * 
     * @return string
     */
    public function getSeverity(): string
    {
        return $this->error->getSeverity();
    }
    
    /**
     * Check if this error should trigger a rollback.
     * 
     * @return bool
     */
    public function shouldRollback(): bool
    {
        return $this->error->shouldRollback();
    }
    
    /**
     * Check if this error is recoverable.
     * 
     * @return bool
     */
    public function isRecoverable(): bool
    {
        return $this->error->isRecoverable();
    }
    
    /**
     * Create an exception for an invalid parameter.
     * 
     * @param string $parameter The parameter name
     * @param string $reason Optional reason for invalidity
     * @return self
     */
    public static function invalidParameter(string $parameter, string $reason = ''): self
    {
        $message = sprintf('Invalid parameter "%s"', $parameter);
        if ($reason) {
            $message .= ': ' . $reason;
        }
        
        return new self(
            HashIdError::INVALID_PARAMETER,
            $message,
            null,
            ['parameter' => $parameter, 'reason' => $reason]
        );
    }
    
    /**
     * Create an exception for decoding failure.
     * 
     * @param string $value The value that failed to decode
     * @param string $hasher Optional hasher name
     * @return self
     */
    public static function decodingFailed(string $value, string $hasher = 'default'): self
    {
        return new self(
            HashIdError::DECODING_FAILED,
            sprintf('Failed to decode value "%s" using hasher "%s"', $value, $hasher),
            null,
            ['value' => $value, 'hasher' => $hasher]
        );
    }
    
    /**
     * Create an exception for encoding failure.
     * 
     * @param mixed $value The value that failed to encode
     * @param string $hasher Optional hasher name
     * @return self
     */
    public static function encodingFailed($value, string $hasher = 'default'): self
    {
        $valueStr = is_scalar($value) ? (string) $value : gettype($value);
        
        return new self(
            HashIdError::ENCODING_FAILED,
            sprintf('Failed to encode value "%s" using hasher "%s"', $valueStr, $hasher),
            null,
            ['value' => $value, 'hasher' => $hasher]
        );
    }
    
    /**
     * Create an exception for hasher not found.
     * 
     * @param string $hasherName The hasher name
     * @param array<string> $availableHashers List of available hashers
     * @return self
     */
    public static function hasherNotFound(string $hasherName, array $availableHashers = []): self
    {
        if (empty($availableHashers)) {
            $message = sprintf('Hasher "%s" not found. No hashers are configured.', $hasherName);
        } else {
            $message = sprintf(
                'Hasher "%s" not found. Available hashers: %s',
                $hasherName,
                implode(', ', $availableHashers)
            );
        }
        
        return new self(
            HashIdError::HASHER_NOT_FOUND,
            $message,
            null,
            ['hasher' => $hasherName, 'available' => $availableHashers]
        );
    }
    
    /**
     * Create an exception for configuration error.
     * 
     * @param string $configKey The configuration key
     * @param string $issue The specific issue
     * @return self
     */
    public static function configurationError(string $configKey, string $issue): self
    {
        return new self(
            HashIdError::CONFIGURATION_ERROR,
            sprintf('Configuration error for "%s": %s', $configKey, $issue),
            null,
            ['config_key' => $configKey, 'issue' => $issue]
        );
    }
    
    /**
     * Create an exception for invalid controller.
     * 
     * @param string $controller The controller string
     * @param string $reason Optional reason
     * @return self
     */
    public static function invalidController(string $controller, string $reason = ''): self
    {
        $message = sprintf('Invalid controller "%s"', $controller);
        if ($reason) {
            $message .= ': ' . $reason;
        }
        
        return new self(
            HashIdError::INVALID_CONTROLLER,
            $message,
            null,
            ['controller' => $controller, 'reason' => $reason]
        );
    }
    
    /**
     * Create an exception for missing class or method.
     * 
     * @param string $class The class name
     * @param string|null $method The method name if applicable
     * @param \Throwable|null $previous Previous exception
     * @return self
     */
    public static function missingClassOrMethod(string $class, ?string $method = null, ?\Throwable $previous = null): self
    {
        if ($method !== null) {
            $message = sprintf('Method "%s::%s" does not exist', $class, $method);
            $context = ['class' => $class, 'method' => $method];
        } else {
            $message = sprintf('Class "%s" does not exist', $class);
            $context = ['class' => $class];
        }
        
        return new self(
            HashIdError::MISSING_CLASS_OR_METHOD,
            $message,
            $previous,
            $context
        );
    }
    
    /**
     * Create an exception for missing dependency.
     * 
     * @param string $dependency The dependency name
     * @param string $required Required for what
     * @return self
     */
    public static function missingDependency(string $dependency, string $required): self
    {
        return new self(
            HashIdError::MISSING_DEPENDENCY,
            sprintf('Missing dependency "%s" required for %s', $dependency, $required),
            null,
            ['dependency' => $dependency, 'required_for' => $required]
        );
    }
}