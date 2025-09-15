<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Exception;

/**
 * Enum representing all possible HashId bundle error types.
 * 
 * Uses PHP 8.1 backed enums for type-safe error handling with
 * built-in messages, codes, and severity levels.
 * 
 * @since 4.0.0
 */
enum HashIdError: string
{
    case INVALID_PARAMETER = 'invalid_parameter';
    case DECODING_FAILED = 'decoding_failed';
    case ENCODING_FAILED = 'encoding_failed';
    case HASHER_NOT_FOUND = 'hasher_not_found';
    case CONFIGURATION_ERROR = 'configuration_error';
    case INVALID_CONTROLLER = 'invalid_controller';
    case MISSING_CLASS_OR_METHOD = 'missing_class_or_method';
    case MISSING_DEPENDENCY = 'missing_dependency';
    
    /**
     * Get the default error message for this error type.
     * 
     * @return string
     */
    public function getMessage(): string
    {
        return match($this) {
            self::INVALID_PARAMETER => 'The provided parameter is invalid for hashing',
            self::DECODING_FAILED => 'Failed to decode the hash value',
            self::ENCODING_FAILED => 'Failed to encode the value',
            self::HASHER_NOT_FOUND => 'The specified hasher configuration was not found',
            self::CONFIGURATION_ERROR => 'Invalid hasher configuration',
            self::INVALID_CONTROLLER => 'Invalid controller format or controller not found',
            self::MISSING_CLASS_OR_METHOD => 'The specified class or method does not exist',
            self::MISSING_DEPENDENCY => 'Required dependency is missing',
        };
    }
    
    /**
     * Get an appropriate HTTP-like status code for this error.
     * 
     * @return int
     */
    public function getCode(): int
    {
        return match($this) {
            self::INVALID_PARAMETER,
            self::DECODING_FAILED,
            self::INVALID_CONTROLLER => 400, // Bad Request
            
            self::HASHER_NOT_FOUND,
            self::MISSING_CLASS_OR_METHOD => 404, // Not Found
            
            self::ENCODING_FAILED,
            self::CONFIGURATION_ERROR,
            self::MISSING_DEPENDENCY => 500, // Internal Server Error
        };
    }
    
    /**
     * Get the severity level of this error.
     * 
     * @return string One of: 'warning', 'error', 'critical'
     */
    public function getSeverity(): string
    {
        return match($this) {
            self::INVALID_PARAMETER,
            self::DECODING_FAILED,
            self::HASHER_NOT_FOUND,
            self::INVALID_CONTROLLER => 'warning',
            
            self::ENCODING_FAILED,
            self::MISSING_CLASS_OR_METHOD => 'error',
            
            self::CONFIGURATION_ERROR,
            self::MISSING_DEPENDENCY => 'critical',
        };
    }
    
    /**
     * Format the error message with additional context.
     * 
     * @param array<string, mixed> $context Additional context for the error
     * @return string Formatted error message
     */
    public function formatWithContext(array $context): string
    {
        $message = $this->getMessage();
        
        if (empty($context)) {
            return $message;
        }
        
        // Add context information
        $contextParts = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $contextParts[] = sprintf('%s: %s', $key, (string) $value);
            }
        }
        
        if (!empty($contextParts)) {
            $message .= ' (' . implode(', ', $contextParts) . ')';
        }
        
        return $message;
    }
    
    /**
     * Create an enum instance from a string value.
     * 
     * @param string $value The error value
     * @return self
     * @throws \ValueError If the value is not valid
     */
    public static function fromValue(string $value): self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
        
        throw new \ValueError(sprintf('Invalid HashIdError value: %s', $value));
    }
    
    /**
     * Check if this error should trigger a rollback in transactions.
     * 
     * @return bool
     */
    public function shouldRollback(): bool
    {
        return match($this) {
            self::CONFIGURATION_ERROR,
            self::MISSING_DEPENDENCY,
            self::ENCODING_FAILED => true,
            default => false,
        };
    }
    
    /**
     * Check if this error is recoverable.
     * 
     * @return bool
     */
    public function isRecoverable(): bool
    {
        return match($this) {
            self::INVALID_PARAMETER,
            self::DECODING_FAILED,
            self::HASHER_NOT_FOUND,
            self::INVALID_CONTROLLER => true,
            default => false,
        };
    }
}