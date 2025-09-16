<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Exception\HashIdError;
use Pgs\HashIdBundle\Exception\HashIdException;

/**
 * Tests for HashIdError enum and HashIdException integration.
 * 
 * @covers \Pgs\HashIdBundle\Exception\HashIdError
 * @covers \Pgs\HashIdBundle\Exception\HashIdException
 */
class HashIdErrorTest extends TestCase
{
    /**
     * Test that all enum cases have proper values.
     */
    public function testEnumCasesHaveExpectedValues(): void
    {
        $this->assertSame('invalid_parameter', HashIdError::INVALID_PARAMETER->value);
        $this->assertSame('decoding_failed', HashIdError::DECODING_FAILED->value);
        $this->assertSame('encoding_failed', HashIdError::ENCODING_FAILED->value);
        $this->assertSame('hasher_not_found', HashIdError::HASHER_NOT_FOUND->value);
        $this->assertSame('configuration_error', HashIdError::CONFIGURATION_ERROR->value);
        $this->assertSame('invalid_controller', HashIdError::INVALID_CONTROLLER->value);
        $this->assertSame('missing_class_or_method', HashIdError::MISSING_CLASS_OR_METHOD->value);
        $this->assertSame('missing_dependency', HashIdError::MISSING_DEPENDENCY->value);
    }
    
    /**
     * Test that getMessage() returns proper messages for each error type.
     */
    public function testGetMessageReturnsCorrectMessages(): void
    {
        $messages = [
            'INVALID_PARAMETER' => 'The provided parameter is invalid for hashing',
            'DECODING_FAILED' => 'Failed to decode the hash value',
            'ENCODING_FAILED' => 'Failed to encode the value',
            'HASHER_NOT_FOUND' => 'The specified hasher configuration was not found',
            'CONFIGURATION_ERROR' => 'Invalid hasher configuration',
            'INVALID_CONTROLLER' => 'Invalid controller format or controller not found',
            'MISSING_CLASS_OR_METHOD' => 'The specified class or method does not exist',
            'MISSING_DEPENDENCY' => 'Required dependency is missing',
        ];
        
        foreach (HashIdError::cases() as $error) {
            $expectedMessage = $messages[$error->name] ?? null;
            if ($expectedMessage !== null) {
                $this->assertSame($expectedMessage, $error->getMessage());
            }
        }
    }
    
    /**
     * Test that getCode() returns appropriate HTTP-like status codes.
     */
    public function testGetCodeReturnsCorrectCodes(): void
    {
        $codes = [
            'INVALID_PARAMETER' => 400,
            'DECODING_FAILED' => 400,
            'ENCODING_FAILED' => 500,
            'HASHER_NOT_FOUND' => 404,
            'CONFIGURATION_ERROR' => 500,
            'INVALID_CONTROLLER' => 400,
            'MISSING_CLASS_OR_METHOD' => 404,
            'MISSING_DEPENDENCY' => 500,
        ];
        
        foreach (HashIdError::cases() as $error) {
            $expectedCode = $codes[$error->name] ?? null;
            if ($expectedCode !== null) {
                $this->assertSame($expectedCode, $error->getCode());
            }
        }
    }
    
    /**
     * Test creating HashIdException with enum.
     */
    public function testHashIdExceptionWithEnum(): void
    {
        $error = HashIdError::INVALID_PARAMETER;
        $exception = new HashIdException($error);
        
        $this->assertSame($error, $exception->getError());
        $this->assertSame($error->getMessage(), $exception->getMessage());
        $this->assertSame($error->getCode(), $exception->getCode());
    }
    
    /**
     * Test creating HashIdException with custom message.
     */
    public function testHashIdExceptionWithCustomMessage(): void
    {
        $error = HashIdError::HASHER_NOT_FOUND;
        $customMessage = 'Custom hasher not found: test_hasher';
        $exception = new HashIdException($error, $customMessage);
        
        $this->assertSame($error, $exception->getError());
        $this->assertSame($customMessage, $exception->getMessage());
        $this->assertSame($error->getCode(), $exception->getCode());
    }
    
    /**
     * Test creating HashIdException with previous exception.
     */
    public function testHashIdExceptionWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $error = HashIdError::ENCODING_FAILED;
        $exception = new HashIdException($error, '', $previous);
        
        $this->assertSame($error, $exception->getError());
        $this->assertSame($previous, $exception->getPrevious());
    }
    
    /**
     * Test that fromValue() static method works correctly.
     */
    public function testFromValueMethod(): void
    {
        $error = HashIdError::fromValue('invalid_parameter');
        $this->assertSame(HashIdError::INVALID_PARAMETER, $error);
        
        $error = HashIdError::fromValue('hasher_not_found');
        $this->assertSame(HashIdError::HASHER_NOT_FOUND, $error);
    }
    
    /**
     * Test that fromValue() throws exception for invalid value.
     */
    public function testFromValueThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        HashIdError::fromValue('invalid_error_type');
    }
    
    /**
     * Test match expression usage with the enum.
     */
    public function testMatchExpressionUsage(): void
    {
        $error = HashIdError::CONFIGURATION_ERROR;
        
        $result = match($error) {
            HashIdError::INVALID_PARAMETER, 
            HashIdError::INVALID_CONTROLLER => 'validation_error',
            HashIdError::HASHER_NOT_FOUND,
            HashIdError::MISSING_CLASS_OR_METHOD => 'not_found_error',
            HashIdError::CONFIGURATION_ERROR,
            HashIdError::ENCODING_FAILED,
            HashIdError::MISSING_DEPENDENCY => 'server_error',
            HashIdError::DECODING_FAILED => 'decode_error',
        };
        
        $this->assertSame('server_error', $result);
    }
    
    /**
     * Test that all enum cases can be iterated.
     */
    public function testEnumCasesCanBeIterated(): void
    {
        $cases = HashIdError::cases();
        
        $this->assertCount(8, $cases);
        $this->assertContainsOnlyInstancesOf(HashIdError::class, $cases);
        
        $values = array_map(fn($case) => $case->value, $cases);
        $this->assertContains('invalid_parameter', $values);
        $this->assertContains('hasher_not_found', $values);
    }
    
    /**
     * Test severity levels for different error types.
     */
    public function testGetSeverityLevels(): void
    {
        $severities = [
            'INVALID_PARAMETER' => 'warning',
            'DECODING_FAILED' => 'warning',
            'ENCODING_FAILED' => 'error',
            'HASHER_NOT_FOUND' => 'warning',
            'CONFIGURATION_ERROR' => 'critical',
            'INVALID_CONTROLLER' => 'warning',
            'MISSING_CLASS_OR_METHOD' => 'error',
            'MISSING_DEPENDENCY' => 'critical',
        ];
        
        foreach (HashIdError::cases() as $error) {
            $expectedSeverity = $severities[$error->name] ?? null;
            if ($expectedSeverity !== null) {
                $this->assertSame($expectedSeverity, $error->getSeverity());
            }
        }
    }
    
    /**
     * Test that error context can be properly formatted.
     */
    public function testFormatWithContext(): void
    {
        $error = HashIdError::HASHER_NOT_FOUND;
        $formatted = $error->formatWithContext(['hasher' => 'custom_hasher']);
        
        $this->assertStringContainsString('hasher configuration was not found', $formatted);
        $this->assertStringContainsString('custom_hasher', $formatted);
    }
    
    /**
     * Test backward compatibility with existing exception handling.
     */
    public function testBackwardCompatibility(): void
    {
        $exception = new HashIdException(HashIdError::INVALID_PARAMETER);
        
        // Should still be catchable as RuntimeException
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        
        // Should work with standard exception methods
        $this->assertIsString($exception->getMessage());
        $this->assertIsInt($exception->getCode());
        $this->assertIsString($exception->getFile());
        $this->assertIsInt($exception->getLine());
        $this->assertIsArray($exception->getTrace());
    }
}