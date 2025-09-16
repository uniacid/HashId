<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Service;

use InvalidArgumentException;
use JsonException;

/**
 * JSON validation service using PHP 8.3's json_validate() function.
 *
 * Provides efficient JSON validation without the overhead of json_decode(),
 * improving performance for API endpoint validation.
 *
 * @since 4.0.0
 */
class JsonValidator
{
    /**
     * Default maximum depth for JSON validation.
     */
    private const DEFAULT_MAX_DEPTH = 512;

    /**
     * Default maximum allowed JSON string size (10MB).
     */
    private const DEFAULT_MAX_JSON_SIZE = 10485760; // 10 * 1024 * 1024
    
    /**
     * Maximum allowed JSON size for this instance.
     */
    private readonly int $maxJsonSize;
    
    /**
     * Constructor with configurable limits.
     * 
     * @param int|null $maxJsonSize Maximum JSON size in bytes (null for default)
     */
    public function __construct(?int $maxJsonSize = null)
    {
        // Allow configuration via environment variable or parameter
        $this->maxJsonSize = $maxJsonSize 
            ?? (int) ($_ENV['JSON_MAX_SIZE'] ?? $_ENV['PGS_HASHID_JSON_MAX_SIZE'] ?? self::DEFAULT_MAX_JSON_SIZE);
        
        // Validate the configured size
        if ($this->maxJsonSize < 1) {
            throw new InvalidArgumentException(
                \sprintf('Maximum JSON size must be positive, got %d', $this->maxJsonSize)
            );
        }
    }

    /**
     * Validate JSON string using PHP 8.3's json_validate() function.
     *
     * @param string $json The JSON string to validate
     * @param int $depth Maximum depth (default: 512)
     * @param int $flags JSON decode flags
     *
     * @return bool True if valid JSON, false otherwise
     */
    public function isValid(string $json, int $depth = self::DEFAULT_MAX_DEPTH, int $flags = 0): bool
    {
        // Security: Check size limit to prevent memory exhaustion
        if (\strlen($json) > $this->maxJsonSize) {
            return false;
        }

        // PHP 8.3+ native json_validate() function
        if (\function_exists('json_validate')) {
            // json_validate only accepts depth parameter, not flags
            return \json_validate($json, $depth);
        }

        // Fallback for PHP < 8.3 (for backward compatibility during transition)
        return $this->isValidFallback($json, $depth, $flags);
    }

    /**
     * Validate JSON with detailed error information.
     *
     * @param string $json The JSON string to validate
     * @param int $depth Maximum depth
     * @param int $flags JSON decode flags
     *
     * @return array{valid: bool, error: ?string, error_code: ?int}
     */
    public function validateWithError(string $json, int $depth = self::DEFAULT_MAX_DEPTH, int $flags = 0): array
    {
        $isValid = $this->isValid($json, $depth, $flags);

        if ($isValid) {
            return [
                'valid' => true,
                'error' => null,
                'error_code' => null,
            ];
        }

        // Get the last JSON error
        $errorCode = \json_last_error();
        $errorMessage = \json_last_error_msg();

        return [
            'valid' => false,
            'error' => $errorMessage,
            'error_code' => $errorCode,
        ];
    }

    /**
     * Validate JSON request body from API endpoint.
     *
     * @param string $content Request body content
     * @param int $maxSize Maximum allowed size in bytes (default: MAX_JSON_SIZE)
     *
     * @return bool True if valid JSON
     */
    public function validateRequestBody(string $content, ?int $maxSize = null): bool
    {
        if (empty($content)) {
            return false;
        }

        // Security: Enforce size limit (use provided or instance default)
        $effectiveMaxSize = $maxSize ?? $this->maxJsonSize;
        if (\strlen($content) > $effectiveMaxSize) {
            return false;
        }

        return $this->isValid($content);
    }

    /**
     * Validate and sanitize JSON for API response.
     *
     * @param mixed $data Data to encode as JSON
     * @param int $flags JSON encode flags
     * @param int $maxSize Maximum allowed size in bytes (default: MAX_JSON_SIZE)
     *
     * @return string|false JSON string or false on failure
     */
    public function validateForResponse($data, int $flags = JSON_THROW_ON_ERROR, ?int $maxSize = null): string|false
    {
        try {
            $json = \json_encode($data, $flags);

            if ($json === false) {
                return false;
            }

            // Security: Check size limit (use provided or instance default)
            $effectiveMaxSize = $maxSize ?? $this->maxJsonSize;
            if (\strlen($json) > $effectiveMaxSize) {
                return false;
            }

            // Validate the encoded JSON
            if (!$this->isValid($json)) {
                return false;
            }

            return $json;
        } catch (JsonException $e) {
            return false;
        }
    }

    /**
     * Check if a string might be JSON (quick pre-check).
     *
     * @param string $string The string to check
     *
     * @return bool True if string looks like JSON
     */
    public function mightBeJson(string $string): bool
    {
        $string = \mb_trim($string);

        if (empty($string)) {
            return false;
        }

        // Check for JSON-like start/end characters
        $firstChar = $string[0];
        $lastChar = $string[\mb_strlen($string) - 1];

        return ($firstChar === '{' && $lastChar === '}') ||
               ($firstChar === '[' && $lastChar === ']') ||
               ($string === 'null') ||
               ($string === 'true') ||
               ($string === 'false') ||
               (\is_numeric($string)) ||
               ($firstChar === '"' && $lastChar === '"');
    }

    /**
     * Fallback validation for PHP < 8.3.
     *
     * @param string $json The JSON string to validate
     * @param int $depth Maximum depth
     * @param int $flags JSON decode flags
     *
     * @return bool True if valid JSON
     */
    private function isValidFallback(string $json, int $depth, int $flags): bool
    {
        // Quick check for obviously invalid JSON
        if (!$this->mightBeJson($json)) {
            return false;
        }

        // Use json_decode for validation (less efficient than json_validate)
        \json_decode($json, null, $depth, $flags);

        return \json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Get the configured maximum JSON size.
     * 
     * @return int Maximum size in bytes
     */
    public function getMaxJsonSize(): int
    {
        return $this->maxJsonSize;
    }
    
    /**
     * Get human-readable error message for JSON error code.
     *
     * @param int $errorCode JSON error code
     *
     * @return string Error message
     */
    public function getErrorMessage(int $errorCode): string
    {
        $errors = [
            JSON_ERROR_NONE => 'No error',
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
            JSON_ERROR_RECURSION => 'Recursive references detected',
            JSON_ERROR_INF_OR_NAN => 'INF or NAN value encountered',
            JSON_ERROR_UNSUPPORTED_TYPE => 'Unsupported type given',
        ];

        // PHP 7.3+
        if (\defined('JSON_ERROR_INVALID_PROPERTY_NAME')) {
            $errors[JSON_ERROR_INVALID_PROPERTY_NAME] = 'Invalid property name';
        }

        // PHP 7.3+
        if (\defined('JSON_ERROR_UTF16')) {
            $errors[JSON_ERROR_UTF16] = 'Malformed UTF-16 characters';
        }

        return $errors[$errorCode] ?? 'Unknown error';
    }
}
