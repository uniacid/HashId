# Security Considerations

## Overview

The HashId Bundle implements several security measures to protect against common vulnerabilities while providing URL parameter obfuscation. This document outlines security features, best practices, and potential risks.

## Security Features

### 1. Protection Against ReDoS Attacks

The bundle implements comprehensive protection against Regular Expression Denial of Service (ReDoS) attacks:

#### Input Validation
- **Length Limits**: Docblocks are limited to 10,000 characters maximum
- **Parameter Limits**: Maximum 20 parameters per annotation
- **String Length**: Individual parameter names limited to 100 characters

#### Safe Regex Patterns
- **Atomic Groups**: Uses `(?>...)` to prevent catastrophic backtracking
- **Possessive Quantifiers**: Uses `++` to avoid unnecessary backtracking
- **Character Class Restrictions**: Limits allowed characters to alphanumeric and underscore

```php
// Safe pattern with atomic group and length limit
if (\preg_match('/@Hash\((?>([^)]{1,500}))\)/', $docComment, $matches)) {
    // Process safely
}
```

### 2. Input Sanitization

All user input is sanitized before processing:

#### Character Validation
- Rejects inputs containing suspicious patterns:
  - HTML/Script tags: `<>` 
  - Null bytes: `\x00`, `\0`
  - Escape sequences: `\x`, `\0`

#### Whitespace Normalization
- Normalizes multiple spaces to single space
- Prevents whitespace-based attacks

### 3. Null Safety

Comprehensive null checking throughout the codebase:

```php
$reflection = $this->reflectionProvider->getMethodReflectionFromClassString(...$args);

// Null safety check
if ($reflection === null) {
    return null;
}
```

### 4. Type Safety

- Strict type declarations (`declare(strict_types=1)`) in all files
- PHPDoc type hints for array shapes and generics
- Runtime type validation

## Security Best Practices

### 1. Salt Configuration

**DO**: Use environment variables for salt configuration
```yaml
# config/packages/pgs_hash_id.yaml
pgs_hash_id:
    salt: '%env(HASHID_SALT)%'
```

**DON'T**: Hardcode salt values in configuration
```yaml
# BAD - Never do this
pgs_hash_id:
    salt: 'my-hardcoded-salt-value'
```

### 2. Alphabet Selection

Choose alphabets that avoid ambiguous characters:
```php
// Good: No ambiguous characters
const SAFE_ALPHABET = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

// Avoid: Characters that look similar
const BAD_ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; // 0/O, 1/l/I
```

### 3. Minimum Hash Length

Set appropriate minimum hash lengths based on your security requirements:

```yaml
pgs_hash_id:
    min_hash_length: 10  # Minimum recommended
    # min_hash_length: 20  # For higher security requirements
```

### 4. Secure Hasher Usage

For sensitive data, use the secure hasher with generated salts:

```php
use Pgs\HashIdBundle\Service\HasherFactory;

class SecureController
{
    public function __construct(
        private readonly HasherFactory $hasherFactory
    ) {}
    
    public function secureAction(): Response
    {
        $hasher = $this->hasherFactory->create('secure');
        $encoded = $hasher->encode($sensitiveId);
        // ...
    }
}
```

## Timing Attack Prevention

The bundle uses constant-time operations where appropriate:

```php
// Use hash_equals for constant-time string comparison
if (hash_equals($expected, $actual)) {
    // Process safely
}
```

## Security Headers

When using HashId Bundle, ensure your application sets appropriate security headers:

```yaml
# config/packages/security.yaml
framework:
    headers:
        X-Content-Type-Options: nosniff
        X-Frame-Options: DENY
        X-XSS-Protection: '1; mode=block'
        Content-Security-Policy: "default-src 'self'"
```

## Enhanced Security Testing Suite

The bundle includes comprehensive security test coverage:

### URL Obfuscation Tests
- Tests that encoded IDs cannot be reverse-engineered without salt
- Validates URL uniqueness and collision prevention
- Ensures consistent encoding/decoding across requests
- Tests obfuscation strength with different alphabets

### Enumeration Prevention Tests
- Verifies sequential IDs produce non-sequential hashes
- Tests entropy and randomness of generated hashes
- Validates resistance to pattern analysis
- Ensures minimum hash length enforcement

### Sequential Attack Tests
- Simulates enumeration attacks with sequential IDs
- Tests rate limiting considerations
- Validates unpredictability of hash patterns
- Tests protection against automated scanning

### Salt Configuration Tests
- Tests salt configuration from environment variables
- Validates salt uniqueness requirements
- Tests salt rotation scenarios
- Verifies secure salt generation

### Input Validation Tests
- Tests SQL injection prevention
- Validates XSS prevention in route parameters
- Tests path traversal protection
- Validates buffer overflow prevention
- Tests command injection prevention

## Vulnerability Reporting

If you discover a security vulnerability, please follow responsible disclosure:

1. **DO NOT** open a public issue
2. Email security details to: security@example.com
3. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if available)

## Security Testing

The bundle includes comprehensive security tests:

```bash
# Run all security tests
vendor/bin/phpunit tests/Security/

# Run specific security test suites
vendor/bin/phpunit tests/Security/UrlObfuscationTest.php
vendor/bin/phpunit tests/Security/EnumerationPreventionTest.php
vendor/bin/phpunit tests/Security/SequentialAttackTest.php
vendor/bin/phpunit tests/Security/SaltConfigurationTest.php
vendor/bin/phpunit tests/Security/InputValidationTest.php
vendor/bin/phpunit tests/Security/ComprehensiveSecurityTest.php
vendor/bin/phpunit tests/Security/RegexInjectionTest.php

# Run static analysis for security issues
vendor/bin/phpstan analyse --level=9 src/

# Check for known vulnerabilities in dependencies
composer audit

# Generate security test coverage report
vendor/bin/phpunit tests/Security/ --coverage-html coverage/security/
```

## Regular Security Audits

Recommended security audit schedule:

- **Weekly**: Run `composer audit` for dependency vulnerabilities
- **Monthly**: Review and update security tests
- **Quarterly**: Perform comprehensive security review
- **Annually**: External security audit (recommended)

## Common Security Mistakes to Avoid

### 1. Exposing Internal IDs

**Wrong**: Using predictable patterns
```php
// BAD: Sequential IDs are still guessable
/order/1 → /order/abc
/order/2 → /order/abd
/order/3 → /order/abe
```

**Right**: Use proper salt and minimum length
```php
// GOOD: Non-predictable hashes
/order/1 → /order/4w9aA11avM
/order/2 → /order/7Zx3mNpQ2k
/order/3 → /order/9kL5vBwR8x
```

### 2. Logging Sensitive Data

**Wrong**: Logging decoded values
```php
// BAD: Logs sensitive IDs
$this->logger->info('Processing order: ' . $decodedId);
```

**Right**: Log only encoded values
```php
// GOOD: Logs only obfuscated values
$this->logger->info('Processing order: ' . $encodedId);
```

### 3. Client-Side Decoding

**Never** implement client-side decoding:
```javascript
// NEVER DO THIS
function decodeHashId(hash) {
    // Client-side decoding exposes your salt
    return hashids.decode(hash);
}
```

## Performance vs Security Trade-offs

| Feature | Performance Impact | Security Benefit |
|---------|-------------------|------------------|
| Long minimum hash length | Minimal | High - Harder to brute force |
| Complex alphabet | Minimal | Medium - More entropy |
| Input validation | Low | High - Prevents attacks |
| Reflection caching | Positive | Neutral |
| Atomic regex groups | Minimal | High - Prevents ReDoS |

## Compliance Considerations

### GDPR
- Hashed IDs are still considered personal data if they can identify individuals
- Implement proper data retention policies
- Ensure right to erasure compliance

### PCI DSS
- If processing payment data, ensure HashIds are not used as sole security measure
- Implement additional encryption for sensitive payment data

## Updates and Patches

Stay updated with security patches:

```bash
# Check for updates
composer outdated pgs/hashid-bundle

# Update to latest secure version
composer update pgs/hashid-bundle

# View security advisories
composer audit
```

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Symfony Security Best Practices](https://symfony.com/doc/current/security.html)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [Regular Expression Denial of Service](https://owasp.org/www-community/attacks/Regular_expression_Denial_of_Service_-_ReDoS)

## Security Checklist

Before deploying to production, ensure:

- [ ] Salt is configured via environment variable
- [ ] Minimum hash length is appropriate for your use case
- [ ] Security tests pass successfully
- [ ] No sensitive data is logged
- [ ] Dependencies are up to date
- [ ] Security headers are configured
- [ ] Input validation is enabled
- [ ] Error messages don't expose sensitive information
- [ ] Rate limiting is implemented for decode operations
- [ ] Monitoring is configured for suspicious patterns

---

*Last updated: 2025-09-15*
*Version: 4.0.0*
*Security Test Coverage: Comprehensive*