<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Service;

use Pgs\HashIdBundle\Service\JsonValidator;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for JsonValidator service.
 *
 * @covers \Pgs\HashIdBundle\Service\JsonValidator
 */
class JsonValidatorTest extends TestCase
{
    private JsonValidator $validator;
    
    protected function setUp(): void
    {
        $this->validator = new JsonValidator();
    }
    
    /**
     * Test validating valid JSON strings.
     */
    public function testValidateValidJson(): void
    {
        $validJsonStrings = [
            '{}',
            '[]',
            '{"key": "value"}',
            '[1, 2, 3]',
            '{"nested": {"key": "value"}}',
            '{"number": 123, "string": "text", "bool": true, "null": null}',
            '"simple string"',
            '123',
            'true',
            'false',
            'null',
        ];
        
        foreach ($validJsonStrings as $json) {
            $this->assertTrue(
                $this->validator->validateRequestBody($json),
                "Failed to validate valid JSON: $json"
            );
        }
    }
    
    /**
     * Test validating invalid JSON strings.
     */
    public function testValidateInvalidJson(): void
    {
        $invalidJsonStrings = [
            '{',
            '}',
            '{"key": }',
            '{"key": "value",}',
            '[1, 2, 3,]',
            '{"key": undefined}',
            "{'key': 'value'}", // Single quotes
            '{key: "value"}', // Unquoted key
            '{"key": "value"]}', // Extra bracket
            '',
            'undefined',
            'NaN',
        ];
        
        foreach ($invalidJsonStrings as $json) {
            $this->assertFalse(
                $this->validator->validateRequestBody($json),
                "Failed to reject invalid JSON: $json"
            );
        }
    }
    
    /**
     * Test validateWithError returns error information.
     */
    public function testValidateWithErrorForInvalidJson(): void
    {
        $invalidJson = '{"key": invalid}';
        
        $result = $this->validator->validateWithError($invalidJson);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('error_code', $result);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['error']);
        $this->assertIsInt($result['error_code']);
        $this->assertGreaterThan(0, $result['error_code']);
    }
    
    /**
     * Test validateWithError for valid JSON.
     */
    public function testValidateWithErrorForValidJson(): void
    {
        $validJson = '{"key": "value"}';
        
        $result = $this->validator->validateWithError($validJson);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('error_code', $result);
        
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
        $this->assertSame(0, $result['error_code']);
    }
    
    /**
     * Test validateForResponse with valid data.
     */
    public function testValidateForResponseWithValidData(): void
    {
        $data = [
            'id' => 123,
            'name' => 'Test',
            'active' => true,
            'tags' => ['tag1', 'tag2'],
            'metadata' => null,
        ];
        
        $json = $this->validator->validateForResponse($data);
        
        $this->assertIsString($json);
        $this->assertNotFalse($json);
        
        // Verify the JSON is valid
        $decoded = json_decode($json, true);
        $this->assertEquals($data, $decoded);
    }
    
    /**
     * Test validateForResponse with invalid data.
     */
    public function testValidateForResponseWithInvalidData(): void
    {
        // Create data that cannot be JSON encoded
        $resource = fopen('php://memory', 'r');
        $data = ['resource' => $resource];
        
        $json = $this->validator->validateForResponse($data);
        
        fclose($resource);
        
        $this->assertFalse($json);
    }
    
    /**
     * Test validateRequestBody with depth validation.
     */
    public function testValidateRequestBodyWithDeepNesting(): void
    {
        // Create deeply nested JSON (default depth limit is usually 512)
        $depth = 100;
        $json = '';
        for ($i = 0; $i < $depth; $i++) {
            $json .= '{"key":';
        }
        $json .= '"value"';
        for ($i = 0; $i < $depth; $i++) {
            $json .= '}';
        }
        
        // Should still be valid within reasonable depth
        $this->assertTrue($this->validator->validateRequestBody($json));
    }
    
    /**
     * Test edge cases for JSON validation.
     */
    public function testJsonValidationEdgeCases(): void
    {
        // Empty string should be invalid
        $this->assertFalse($this->validator->validateRequestBody(''));
        
        // Whitespace only should be invalid
        $this->assertFalse($this->validator->validateRequestBody('   '));
        
        // Valid JSON with whitespace
        $this->assertTrue($this->validator->validateRequestBody('  {"key": "value"}  '));
        
        // Unicode characters
        $this->assertTrue($this->validator->validateRequestBody('{"emoji": "😀", "text": "こんにちは"}'));
        
        // Large numbers
        $this->assertTrue($this->validator->validateRequestBody('{"bignum": 9999999999999999999}'));
        
        // Scientific notation
        $this->assertTrue($this->validator->validateRequestBody('{"scientific": 1.23e10}'));
    }
    
    /**
     * Test validateForResponse preserves JSON options.
     */
    public function testValidateForResponsePreservesOptions(): void
    {
        $data = [
            'html' => '<div>Test</div>',
            'slash' => 'path/to/file',
            'unicode' => '你好',
        ];
        
        $json = $this->validator->validateForResponse($data);
        
        $this->assertIsString($json);
        
        // Check that special characters are properly handled
        $this->assertStringContainsString('<div>Test</div>', $json);
        $this->assertStringContainsString('path/to/file', $json);
        // Unicode might be escaped or not depending on implementation
        $decoded = json_decode($json, true);
        $this->assertEquals($data, $decoded);
    }
    
    /**
     * Test handling of different PHP 8.3 json_validate scenarios.
     */
    public function testPhp83JsonValidateCompatibility(): void
    {
        // Test that the validator works correctly regardless of PHP version
        $testCases = [
            '{"valid": true}' => true,
            '{"invalid": }' => false,
            '[]' => true,
            '' => false,
            'null' => true,
        ];
        
        foreach ($testCases as $json => $expected) {
            $this->assertSame(
                $expected,
                $this->validator->validateRequestBody($json),
                "Failed for JSON: $json"
            );
        }
    }
}