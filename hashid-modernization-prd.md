# Product Requirements Document
## HashId Bundle Modernization for PHP 8+ and Symfony 6.4

### Executive Summary
This document outlines the requirements and plan for forking and modernizing the PGSSoft/HashId Symfony bundle to support PHP 8+ and Symfony 6.4, while maintaining backward compatibility where possible and improving the codebase with modern PHP features.

### Project Overview

**Current State:**
- Package: `pgs-soft/hashid-bundle`
- Latest Version: 3.1.0 (April 2023)
- PHP Support: 7.2.5+
- Symfony Support: 4.4, 5.0
- Main Purpose: Encode/decode integer URL parameters using hashids.org
- Last significant update: Limited maintenance, 8 open issues

**Target State:**
- PHP Support: 8.1+ (minimum)
- Symfony Support: 6.4 LTS, 7.0
- Modern PHP features implementation
- Improved documentation and testing

### Technical Requirements

#### 1. PHP Version Support
- **Minimum PHP Version:** 8.1
- **Rationale:**
  - PHP 8.1 is the minimum for Symfony 6.1+
  - Enables use of modern PHP features (enums, readonly properties, etc.)
  - PHP 7.4 reached end-of-life in November 2022

#### 2. Symfony Version Support
- **Primary Target:** Symfony 6.4 LTS
- **Secondary Target:** Symfony 7.0
- **Deprecate:** Symfony 4.x and 5.x support in the new major version

#### 3. Dependencies Update

**Core Dependencies to Update:**
```json
{
  "php": ">=8.1",
  "symfony/dependency-injection": "^6.4|^7.0",
  "symfony/config": "^6.4|^7.0",
  "symfony/routing": "^6.4|^7.0",
  "symfony/http-kernel": "^6.4|^7.0",
  "doctrine/annotations": "^2.0",
  "hashids/hashids": "^4.0|^5.0"
}
```

**Development Dependencies:**
```json
{
  "phpstan/phpstan": "^1.10",
  "phpunit/phpunit": "^10.0",
  "symfony/phpunit-bridge": "^6.4|^7.0",
  "friendsofphp/php-cs-fixer": "^3.40",
  "symfony/browser-kit": "^6.4|^7.0",
  "symfony/yaml": "^6.4|^7.0"
}
```

### Feature Modernization

#### 4. Replace Annotations with PHP Attributes
**Current Implementation:**
```php
use Pgs\HashIdBundle\Annotation\Hash;

/**
 * @Hash("id")
 */
public function edit(int $id) { }
```

**New Implementation:**
```php
use Pgs\HashIdBundle\Attribute\Hash;

#[Hash('id')]
public function edit(int $id) { }

// Support multiple parameters
#[Hash(['id', 'other'])]
public function test(int $id, int $other) { }
```

**Migration Strategy:**
- Maintain both annotations and attributes in version 4.0 with deprecation notices
- Remove annotation support in version 5.0

#### 5. Configuration Updates
**Modern Configuration Structure:**
```yaml
# config/packages/hash_id.yaml
hash_id:
  converter:
    enabled: true
    passthrough: false
    auto_convert: false
  hashids:
    salt: '%env(HASHIDS_SALT)%'
    min_hash_length: 20
    alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
  # New feature: multiple hashers
  hashers:
    default:
      salt: '%env(HASHIDS_SALT)%'
      min_hash_length: 20
    secure:
      salt: '%env(HASHIDS_SECURE_SALT)%'
      min_hash_length: 32
      alphabet: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
```

#### 5.1 PHP 8.3 Specific Enhancements

**Typed Class Constants:**
```php
interface HashIdConfigInterface
{
    // PHP 8.3 typed constants
    public const int MIN_LENGTH = 10;
    public const int MAX_LENGTH = 255;
    public const string DEFAULT_ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
}

class HashIdConfig implements HashIdConfigInterface
{
    // Typed constants are enforced in implementations
    public const int MIN_LENGTH = 10;
    public const int MAX_LENGTH = 255;
    public const string DEFAULT_ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
}
```

**Dynamic Class Constant Fetch (PHP 8.3):**
```php
class HashIdProvider
{
    public const string ENCODER_DEFAULT = 'default';
    public const string ENCODER_SECURE = 'secure';

    public function getEncoder(string $type): HashidsInterface
    {
        // PHP 8.3 dynamic constant fetch
        $encoderType = self::{strtoupper('ENCODER_' . $type)};
        return $this->encoders[$encoderType];
    }
}
```

**JSON Validation with json_validate() (PHP 8.3):**
```php
class HashIdApiController
{
    #[Route('/api/encode', methods: ['POST'])]
    public function encode(Request $request): JsonResponse
    {
        $content = $request->getContent();

        // PHP 8.3 json_validate() - more efficient than json_decode
        if (!json_validate($content)) {
            throw new BadRequestException('Invalid JSON payload');
        }

        $data = json_decode($content, true);
        // Process encoding...
    }
}
```

**Anonymous Readonly Classes for Testing (PHP 8.3):**
```php
class HashIdConverterTest extends TestCase
{
    public function testWithMockConfiguration(): void
    {
        // PHP 8.3 anonymous readonly class
        $config = new readonly class {
            public function __construct(
                public string $salt = 'test',
                public int $minLength = 10
            ) {}
        };

        $converter = new HashIdConverter($config);
        // ... test assertions
    }
}
```

#### 6. Code Modernization

**PHP 8 Features to Implement:**
- Constructor property promotion
- Readonly properties
- Union types
- Match expressions
- Named arguments support
- Nullsafe operators
- Native attributes

**Example Refactoring:**
```php
// Before (PHP 7.x)
class HashIdConverter
{
    private $hashids;
    private $passthrough;

    public function __construct(HashidsInterface $hashids, bool $passthrough = false)
    {
        $this->hashids = $hashids;
        $this->passthrough = $passthrough;
    }
}

// After (PHP 8.1+)
class HashIdConverter
{
    public function __construct(
        private readonly HashidsInterface $hashids,
        private readonly bool $passthrough = false
    ) {}
}
```

### Testing Requirements

#### 7. Test Coverage
- **Minimum Coverage:** 90%
- **Test Framework:** PHPUnit 10.x
- **Test Types:**
  - Unit tests for all services
  - Integration tests for Symfony integration
  - Functional tests for controllers
  - Deprecation tests for backward compatibility

#### 8. CI/CD Pipeline
```yaml
# .github/workflows/ci.yml
matrix:
  php: ['8.1', '8.2', '8.3']
  symfony: ['6.4.*', '7.0.*']
  dependencies: ['lowest', 'highest']
```

### Documentation Requirements

#### 9. Documentation Updates
- **README.md:** Complete rewrite with modern examples
- **UPGRADE.md:** Migration guide from 3.x to 4.x
- **CHANGELOG.md:** Detailed change documentation
- **Examples:** Add `/examples` directory with full Symfony app examples

#### 10. Migration Guide Structure
```markdown
# Upgrading from 3.x to 4.x

## Breaking Changes
- Minimum PHP version is now 8.1
- Minimum Symfony version is now 6.4
- Annotations are deprecated in favor of attributes

## Migration Steps
1. Update PHP to 8.1+
2. Update Symfony to 6.4+
3. Replace annotations with attributes
4. Update configuration format
...
```

### Implementation Plan

#### Phase 1: Setup and Foundation (Week 1)
- [ ] Fork repository
- [ ] Set up development environment
- [ ] Configure CI/CD pipeline
- [ ] Update composer.json dependencies
- [ ] Set up code quality tools (PHPStan, CS-Fixer)

#### Phase 2: Core Modernization (Week 2-3)
- [ ] Update minimum PHP/Symfony versions
- [ ] Implement PHP 8 features (property promotion, etc.)
- [ ] Create attribute classes alongside annotations
- [ ] Update service definitions for Symfony 6.4
- [ ] Fix deprecations and compatibility issues

#### Phase 3: Feature Enhancement (Week 4)
- [ ] Implement multiple hasher support
- [ ] Add typed properties throughout
- [ ] Improve error handling with enums
- [ ] Add better IDE support with generics

#### Phase 4: Testing and Documentation (Week 5)
- [ ] Write/update unit tests
- [ ] Create integration tests
- [ ] Update all documentation
- [ ] Create migration guide
- [ ] Add example applications

#### Phase 5: Release Preparation (Week 6)
- [ ] Beta testing
- [ ] Performance benchmarking
- [ ] Security audit
- [ ] Prepare release notes
- [ ] Tag version 4.0.0

### Backward Compatibility Strategy

#### Version 4.0 (Transitional)
- Support both annotations and attributes
- Emit deprecation warnings for annotations
- Maintain similar API surface
- Provide compatibility layer for old configuration

#### Version 5.0 (Clean)
- Remove annotation support completely
- Remove deprecated configuration options
- Require PHP 8.2+
- Full Symfony 7.0 support

### Success Metrics

1. **Compatibility:**
   - 100% compatibility with Symfony 6.4 and 7.0
   - All tests passing on PHP 8.1, 8.2, 8.3

2. **Code Quality:**
   - PHPStan level 9 compliance
   - PSR-12 coding standards
   - 90%+ test coverage

3. **Performance:**
   - No performance regression from v3.x
   - Benchmark improvements with PHP 8 optimizations

4. **Adoption:**
   - Clear migration path
   - Comprehensive documentation
   - Active community support

### Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|------------|--------|------------|
| Breaking changes affect existing users | High | Medium | Provide compatibility layer, clear migration guide |
| Annotation deprecation causes issues | Medium | Low | Support both in v4.0, clear deprecation timeline |
| Performance regression | Low | High | Comprehensive benchmarking, optimization |
| Limited adoption | Medium | Medium | Good documentation, maintain v3.x branch |

### Alternative Considerations

**Alternative Package:** `roukmoute/hashids-bundle`
- Already supports Symfony 6.x
- Active maintenance
- Consider contributing to this instead of forking PGSSoft

**Recommendation:** Evaluate if contributing to roukmoute/hashids-bundle would be more beneficial than maintaining a separate fork.

### Conclusion

This modernization project will fork the PGSSoft/HashId bundle and bring it up to current PHP and Symfony standards while providing a clear migration path for existing users. The phased approach ensures stability while introducing modern features progressively. By maintaining namespace compatibility and providing migration tools, we can ensure a smooth transition for current PGSSoft/HashId users.

### Next Steps

1. **Fork Repository:** Fork https://github.com/PGSSoft/HashId to your GitHub account
2. **Initial Assessment:** Review existing code and open issues
3. **Development Environment:** Set up PHP 8.3 and Symfony 6.4/7.0 environment
4. **Community Announcement:** Announce the fork and gather early feedback
5. **Begin Development:** Start with Phase 0 and Phase 1 implementation

### Appendix: Initial Tasks Checklist

#### Immediate Actions (Day 1):
- [ ] Fork the PGSSoft/HashId repository
- [ ] Clone locally and create development branch
- [ ] Set up PHP 8.3 development environment
- [ ] Install Rector and create initial configuration
- [ ] Run Rector in dry-run mode to assess scope of changes
- [ ] Run existing tests to establish baseline
- [ ] Document all deprecation warnings
- [ ] Create GitHub Project board for tracking progress

#### Rector Workflow Best Practices:

1. **Incremental Application:**
```bash
# Create separate configs for each stage
rector-php81.php  # PHP 8.1 features only
rector-php82.php  # PHP 8.2 features only
rector-php83.php  # PHP 8.3 features only
rector-symfony.php # Symfony specific upgrades
rector-quality.php # Code quality improvements
```

2. **Review Process:**
```bash
# Always dry-run first
vendor/bin/rector process --dry-run > rector-changes.txt

# Review the changes
git diff

# If satisfied, apply and commit
vendor/bin/rector process
git add -A
git commit -m "refactor: apply Rector rules for [specific change]"
```

3. **Custom Rules Creation:**
- Create custom rules for HashId-specific patterns
- Store in `rector-rules/` directory
- Test thoroughly with fixture files

4. **Rector + Manual Work Division:**

**Let Rector Handle:**
- PHP version syntax upgrades
- Annotation to attribute conversion
- Type declarations
- Dead code removal
- Code quality improvements

**Manual Work Required:**
- Business logic changes
- Complex migration patterns
- Framework-specific configurations
- Documentation updates
- Test updates for new features

#### First Week Goals:
- [ ] Fix PHP 8.x compatibility issues
- [ ] Update composer.json with new requirements
- [ ] Set up GitHub Actions CI/CD
- [ ] Create initial migration documentation
- [ ] Open discussion issue about the fork for community input
- [ ] Begin converting annotations to attributes

### Contact and Contribution

Once the fork is established, consider:
- Creating a discussion forum for migration questions
- Setting up a Discord/Slack channel for real-time support
- Establishing contribution guidelines
- Creating a roadmap for future features
- Reaching out to PGSSoft to inform them about the fork and offer collaboration