# Breaking Changes in HashId Bundle 4.0

This document lists all breaking changes when upgrading from HashId Bundle 3.x to 4.0.

## Summary

HashId Bundle 4.0 is a major release that modernizes the codebase for PHP 8.1+ and Symfony 6.4+. While we've maintained backward compatibility where possible, some breaking changes were necessary to leverage modern PHP features and improve performance.

## Breaking Changes by Category

### PHP Version Requirements

| Component | Version 3.x | Version 4.0 | Impact |
|-----------|------------|-------------|---------|
| Minimum PHP | 7.2 | 8.1 | **BREAKING** |
| Recommended PHP | 7.4 | 8.3 | Performance |
| Maximum PHP | 8.0 | 8.3 | Compatibility |

**Migration Required:** Upgrade PHP to at least 8.1 before upgrading the bundle.

### Symfony Version Requirements

| Component | Version 3.x | Version 4.0 | Impact |
|-----------|------------|-------------|---------|
| Minimum Symfony | 4.4 | 6.4 LTS | **BREAKING** |
| Supported Versions | 4.4, 5.x | 6.4 LTS, 7.0 | **BREAKING** |

**Migration Required:** Upgrade Symfony to 6.4 LTS or 7.0 before upgrading the bundle.

### Dependency Changes

#### Removed Dependencies

- `doctrine/annotations` - No longer required when using attributes exclusively
- `symfony/framework-bundle` < 6.4 - Older versions not supported

#### Updated Dependencies

| Package | Version 3.x | Version 4.0 |
|---------|------------|-------------|
| hashids/hashids | ^3.0 \|\| ^4.0 | ^4.0 \|\| ^5.0 |
| symfony/config | ^4.4 \|\| ^5.0 | ^6.4 \|\| ^7.0 |
| symfony/dependency-injection | ^4.4 \|\| ^5.0 | ^6.4 \|\| ^7.0 |
| symfony/http-kernel | ^4.4 \|\| ^5.0 | ^6.4 \|\| ^7.0 |

### Annotation System Changes

#### Deprecations

```php
// DEPRECATED in 4.0, removed in 5.0
use Pgs\HashIdBundle\Annotation\Hash;

/**
 * @Hash("id")
 */
public function action(int $id) {}
```

#### New Attribute System

```php
// NEW in 4.0, required in 5.0
use Pgs\HashIdBundle\Attribute\Hash;

#[Hash('id')]
public function action(int $id) {}
```

**Impact:** While annotations still work in 4.0, they trigger deprecation warnings. Plan to migrate to attributes before 5.0.

### Service Name Changes

| Old Service ID | New Service ID | Type |
|----------------|----------------|------|
| `pgs_hash_id.parameters_processor` | `Pgs\HashIdBundle\Service\ParametersProcessor` | **BREAKING** |
| `pgs_hash_id.hashids_converter` | `Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter` | **BREAKING** |
| `pgs_hash_id.annotation_provider` | `Pgs\HashIdBundle\Service\AttributeProvider` | **BREAKING** |
| `pgs_hash_id.router_decorator` | `Pgs\HashIdBundle\Decorator\RouterDecorator` | **BREAKING** |

**Migration Required:** Update all service references in your configuration files.

### Class and Interface Changes

#### Renamed Classes

| Old Class | New Class | Reason |
|-----------|-----------|---------|
| `AnnotationProvider` | `AttributeProvider` | Reflects shift to attributes |

#### Updated Method Signatures

```php
// Version 3.x
public function __construct(Reader $reader, ParametersProcessor $processor)

// Version 4.0
public function __construct(
    private readonly AttributeReader $reader,
    private readonly ParametersProcessor $processor
)
```

### Configuration Changes

#### New Configuration Options

```yaml
# New in 4.0
pgs_hash_id:
    compatibility:
        suppress_deprecations: false  # New
        prefer_attributes: true        # New
        legacy_mode: false            # New
```

#### Removed Configuration Options

None - all v3.x configuration options are still supported.

### Internal API Changes

#### ParametersProcessor

```php
// Version 3.x
public function process($parameters, $route);

// Version 4.0
public function process(array $parameters, string $route): array;
```

#### HashidsConverter

```php
// Version 3.x
public function encode($value);
public function decode($value);

// Version 4.0
public function encode(int|string $value): string;
public function decode(string $value): int|false;
```

### Behavioral Changes

#### Error Handling

- **v3.x:** Silent failures with null returns
- **v4.0:** Throws typed exceptions for better debugging

```php
// Version 3.x
$encoded = $converter->encode($id); // Returns null on failure

// Version 4.0
try {
    $encoded = $converter->encode($id);
} catch (EncodingException $e) {
    // Handle error
}
```

#### Type Strictness

- **v3.x:** Loose typing, automatic type coercion
- **v4.0:** Strict typing with type declarations

```php
// Version 3.x - accepts string or int
public function setId($id)

// Version 4.0 - explicit types
public function setId(int|string $id): void
```

### Testing Changes

#### PHPUnit Version

- **v3.x:** PHPUnit 8.x or 9.x
- **v4.0:** PHPUnit 10.x required

#### Test Configuration

```xml
<!-- Version 3.x -->
<phpunit bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="HashId Bundle">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>

<!-- Version 4.0 -->
<phpunit
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
    bootstrap="tests/bootstrap.php"
    executionOrder="depends,defects"
    beStrictAboutOutputDuringTests="true">
    <testsuites>
        <testsuite name="HashId Bundle">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <coverage/>
</phpunit>
```

### Deprecation Timeline

| Feature | Deprecated | Removed | Migration Path |
|---------|------------|---------|----------------|
| `@Hash` annotation | 4.0 | 5.0 | Use `#[Hash]` attribute |
| `@Route` annotation | 4.0 | 5.0 | Use `#[Route]` attribute |
| Service aliases | 4.0 | 5.0 | Use FQCN service IDs |
| PHP 8.0 support | 4.0 | 5.0 | Upgrade to PHP 8.1+ |
| Symfony 6.3 | 4.0 | 5.0 | Use Symfony 6.4 LTS or 7.0 |
| Untyped parameters | 4.0 | 5.0 | Add type declarations |

## Impact Assessment

### High Impact Changes

1. **PHP Version Requirement** - Requires infrastructure update
2. **Symfony Version Requirement** - May affect other bundles
3. **Service Name Changes** - Requires configuration updates

### Medium Impact Changes

1. **Annotation to Attribute Migration** - Can be automated with Rector
2. **PHPUnit Upgrade** - May require test adjustments
3. **Type Strictness** - May reveal hidden bugs

### Low Impact Changes

1. **New Configuration Options** - Optional, backward compatible
2. **Internal API Updates** - Only affects custom extensions
3. **Dependency Updates** - Usually handled by Composer

## Migration Priority

### Must Do Immediately

1. Upgrade PHP to 8.1+
2. Upgrade Symfony to 6.4 or 7.0
3. Update service references

### Should Do Soon

1. Migrate annotations to attributes
2. Update tests for PHPUnit 10
3. Add type declarations

### Can Do Later

1. Optimize for PHP 8.3 features
2. Remove compatibility mode
3. Clean up deprecated code

## Rollback Considerations

### Can Rollback If

- You haven't removed annotations
- You maintain PHP 7.4 compatibility in your code
- You haven't used PHP 8.1+ specific features

### Cannot Rollback If

- You've removed all annotations
- You're using PHP 8.1+ features (readonly, enums, etc.)
- You've upgraded other bundles that require Symfony 6.4+

## Getting Help

If you encounter issues with breaking changes:

1. Check the [Migration FAQ](docs/migration-faq.md)
2. Review the [Manual Migration Guide](docs/manual-migration.md)
3. Use the [Rector Migration Guide](docs/rector-migration.md)
4. Open a GitHub issue with:
   - Your current version
   - Target version
   - Error messages
   - Minimal reproduction example

## Version 5.0 Preview

Version 5.0 (planned for late 2024) will include additional breaking changes:

- Complete removal of annotation support
- PHP 8.2+ requirement
- Symfony 7.0+ only
- Removal of all deprecated features
- Performance optimizations that may change behavior

Start planning your migration to attributes now to be ready for 5.0!