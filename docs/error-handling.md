# Error Handling with HashIdError Enum

## Overview

Starting with version 4.0, the HashId Bundle uses PHP 8.1 backed enums for type-safe error handling. This provides better IDE support, prevents typos, and enables modern PHP features like match expressions.

## HashIdError Enum

The `HashIdError` enum defines all possible error types in the bundle:

```php
use Pgs\HashIdBundle\Exception\HashIdError;
use Pgs\HashIdBundle\Exception\HashIdException;

// Available error types
HashIdError::INVALID_PARAMETER       // 400 - Invalid parameter for hashing
HashIdError::DECODING_FAILED        // 400 - Failed to decode hash value
HashIdError::ENCODING_FAILED        // 500 - Failed to encode value
HashIdError::HASHER_NOT_FOUND       // 404 - Hasher configuration not found
HashIdError::CONFIGURATION_ERROR    // 500 - Invalid configuration
HashIdError::INVALID_CONTROLLER     // 400 - Invalid controller format
HashIdError::MISSING_CLASS_OR_METHOD // 404 - Class or method doesn't exist
HashIdError::MISSING_DEPENDENCY     // 500 - Required dependency missing
```

## Using Match Expressions

PHP 8's match expressions work perfectly with the HashIdError enum for elegant error handling:

### Example 1: Error Response Handling

```php
use Pgs\HashIdBundle\Exception\HashIdError;
use Pgs\HashIdBundle\Exception\HashIdException;
use Symfony\Component\HttpFoundation\Response;

try {
    // Your HashId operations
    $decoded = $hasherRegistry->getConverter($hasherName)->decode($hash);
} catch (HashIdException $e) {
    $statusCode = match($e->getError()) {
        HashIdError::INVALID_PARAMETER,
        HashIdError::DECODING_FAILED,
        HashIdError::INVALID_CONTROLLER => Response::HTTP_BAD_REQUEST,
        
        HashIdError::HASHER_NOT_FOUND,
        HashIdError::MISSING_CLASS_OR_METHOD => Response::HTTP_NOT_FOUND,
        
        HashIdError::ENCODING_FAILED,
        HashIdError::CONFIGURATION_ERROR,
        HashIdError::MISSING_DEPENDENCY => Response::HTTP_INTERNAL_SERVER_ERROR,
    };
    
    return new Response($e->getMessage(), $statusCode);
}
```

### Example 2: Logging with Severity Levels

```php
use Pgs\HashIdBundle\Exception\HashIdError;
use Pgs\HashIdBundle\Exception\HashIdException;
use Psr\Log\LoggerInterface;

class ErrorHandler
{
    public function __construct(private LoggerInterface $logger) {}
    
    public function handle(HashIdException $e): void
    {
        $logLevel = match($e->getError()->getSeverity()) {
            'warning' => 'warning',
            'error' => 'error',
            'critical' => 'critical',
        };
        
        $this->logger->$logLevel($e->getMessage(), [
            'error_type' => $e->getError()->value,
            'context' => $e->getContext(),
            'severity' => $e->getSeverity(),
        ]);
    }
}
```

### Example 3: Recovery Strategy Selection

```php
use Pgs\HashIdBundle\Exception\HashIdError;
use Pgs\HashIdBundle\Exception\HashIdException;

class HashIdService
{
    public function processHash(string $hash): mixed
    {
        try {
            return $this->decode($hash);
        } catch (HashIdException $e) {
            // Determine recovery strategy based on error type
            return match($e->getError()) {
                // For recoverable errors, return null or default value
                HashIdError::INVALID_PARAMETER,
                HashIdError::DECODING_FAILED => null,
                
                // For hasher not found, try default hasher
                HashIdError::HASHER_NOT_FOUND => $this->tryDefaultHasher($hash),
                
                // For critical errors, re-throw
                HashIdError::CONFIGURATION_ERROR,
                HashIdError::MISSING_DEPENDENCY => throw $e,
                
                // For other errors, log and return null
                default => $this->logAndReturnNull($e),
            };
        }
    }
}
```

### Example 4: Error Categorization

```php
use Pgs\HashIdBundle\Exception\HashIdError;

class ErrorAnalyzer
{
    public function categorizeError(HashIdError $error): string
    {
        return match(true) {
            $error->isRecoverable() => 'user_error',
            $error->shouldRollback() => 'system_failure',
            $error->getSeverity() === 'critical' => 'critical_issue',
            default => 'operational_error',
        };
    }
    
    public function getErrorGroup(HashIdError $error): string
    {
        return match($error) {
            HashIdError::INVALID_PARAMETER,
            HashIdError::INVALID_CONTROLLER => 'validation_errors',
            
            HashIdError::HASHER_NOT_FOUND,
            HashIdError::MISSING_CLASS_OR_METHOD => 'not_found_errors',
            
            HashIdError::DECODING_FAILED,
            HashIdError::ENCODING_FAILED => 'conversion_errors',
            
            HashIdError::CONFIGURATION_ERROR,
            HashIdError::MISSING_DEPENDENCY => 'setup_errors',
        };
    }
}
```

## Creating Custom Exceptions

You can create custom exceptions using the static factory methods:

```php
use Pgs\HashIdBundle\Exception\HashIdException;

// Invalid parameter with specific reason
throw HashIdException::invalidParameter('user_id', 'Must be a positive integer');

// Decoding failure with context
throw HashIdException::decodingFailed('abc123', 'custom_hasher');

// Hasher not found with available options
throw HashIdException::hasherNotFound('unknown', ['default', 'secure', 'public']);

// Configuration error with details
throw HashIdException::configurationError('alphabet', 'Contains duplicate characters');

// Missing class or method
throw HashIdException::missingClassOrMethod('App\\Controller\\UserController', 'showAction');
```

## Handling Errors by Severity

```php
use Pgs\HashIdBundle\Exception\HashIdError;
use Pgs\HashIdBundle\Exception\HashIdException;

class ApplicationErrorHandler
{
    public function handle(\Throwable $e): void
    {
        if (!$e instanceof HashIdException) {
            // Handle other exceptions
            return;
        }
        
        match($e->getError()->getSeverity()) {
            'warning' => $this->handleWarning($e),
            'error' => $this->handleError($e),
            'critical' => $this->handleCritical($e),
        };
    }
    
    private function handleWarning(HashIdException $e): void
    {
        // Log warning, continue execution
        // These are typically user input errors
    }
    
    private function handleError(HashIdException $e): void
    {
        // Log error, may need intervention
        // These are typically system errors that need attention
    }
    
    private function handleCritical(HashIdException $e): void
    {
        // Alert administrators, possible system failure
        // These require immediate attention
    }
}
```

## Migration from String-Based Errors

If you're migrating from version 3.x, here's how to update your error handling:

### Before (v3.x)
```php
try {
    // operations
} catch (\InvalidArgumentException $e) {
    if (str_contains($e->getMessage(), 'hasher')) {
        // handle hasher error
    } elseif (str_contains($e->getMessage(), 'parameter')) {
        // handle parameter error
    }
}
```

### After (v4.0)
```php
try {
    // operations
} catch (HashIdException $e) {
    match($e->getError()) {
        HashIdError::HASHER_NOT_FOUND => // handle hasher error,
        HashIdError::INVALID_PARAMETER => // handle parameter error,
        default => // handle other errors,
    };
}
```

## Backward Compatibility

The legacy exception classes are still available but deprecated:
- `InvalidControllerException` → Use `HashIdException::invalidController()`
- `MissingClassOrMethodException` → Use `HashIdException::missingClassOrMethod()`
- `MissingDependencyException` → Use `HashIdException::missingDependency()`
- `HasherNotFoundException` → Use `HashIdException::hasherNotFound()`

These will be removed in version 5.0.

## Best Practices

1. **Use match expressions** for clean, exhaustive error handling
2. **Check severity levels** to determine appropriate response actions
3. **Use static factory methods** for creating exceptions with proper context
4. **Log error context** using the `getContext()` method for debugging
5. **Handle errors by category** rather than individual types when appropriate
6. **Consider recovery strategies** based on the `isRecoverable()` method