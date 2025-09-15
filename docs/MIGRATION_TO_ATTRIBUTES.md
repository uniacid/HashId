# Migration Guide: From Annotations to Attributes

## Overview

HashId Bundle v4.0 introduces support for PHP 8 attributes while maintaining backward compatibility with annotations. This guide will help you migrate from the legacy `@Hash` annotation to the modern `#[Hash]` attribute.

## Why Migrate?

- **Native PHP Support**: Attributes are a native PHP 8+ feature, no external dependencies required
- **Better Performance**: Attributes are parsed at compile time, not runtime
- **IDE Support**: Better autocomplete and refactoring support in modern IDEs
- **Future-Proof**: Annotations will be removed in v5.0

## Migration Timeline

- **v4.0** (Current): Dual support for both annotations and attributes
- **v4.x**: Deprecation warnings for annotation usage (configurable)
- **v5.0**: Annotations removed, attributes only

## Quick Comparison

### Before (Annotations)
```php
use Pgs\HashIdBundle\Annotation\Hash;

class ProductController
{
    /**
     * @Hash("id")
     */
    public function show(int $id): Response
    {
        // Controller logic
    }
    
    /**
     * @Hash({"productId", "categoryId"})
     */
    public function showInCategory(int $productId, int $categoryId): Response
    {
        // Controller logic
    }
}
```

### After (Attributes)
```php
use Pgs\HashIdBundle\Attribute\Hash;

class ProductController
{
    #[Hash('id')]
    public function show(int $id): Response
    {
        // Controller logic
    }
    
    #[Hash(['productId', 'categoryId'])]
    public function showInCategory(int $productId, int $categoryId): Response
    {
        // Controller logic
    }
}
```

## Step-by-Step Migration

### Step 1: Update Your PHP Version

Ensure you're running PHP 8.0 or higher:
```bash
php -v
```

### Step 2: Update Import Statements

Change your import from the annotation namespace to the attribute namespace:

```php
// Old
use Pgs\HashIdBundle\Annotation\Hash;

// New
use Pgs\HashIdBundle\Attribute\Hash;
```

### Step 3: Convert Annotations to Attributes

#### Single Parameter

```php
// Old
/**
 * @Hash("id")
 */
public function show(int $id) {}

// New
#[Hash('id')]
public function show(int $id) {}
```

#### Multiple Parameters

```php
// Old
/**
 * @Hash({"id", "parentId", "userId"})
 */
public function complex(int $id, int $parentId, int $userId) {}

// New
#[Hash(['id', 'parentId', 'userId'])]
public function complex(int $id, int $parentId, int $userId) {}
```

### Step 4: Configure Deprecation Warnings

During migration, you can control deprecation warnings in your configuration:

```yaml
# config/packages/pgs_hash_id.yaml
pgs_hash_id:
    # ... other configuration
    compatibility:
        deprecation_warnings: true  # Show warnings for annotation usage
        prefer_attributes: true      # Prefer attributes when both are present
```

To suppress warnings during the transition period:

```yaml
pgs_hash_id:
    compatibility:
        deprecation_warnings: false
```

### Step 5: Test Your Application

Run your test suite to ensure everything works:

```bash
vendor/bin/phpunit
```

## Gradual Migration Strategy

You don't need to migrate all controllers at once. The bundle supports both approaches simultaneously:

```php
class MixedController
{
    // New method using attribute
    #[Hash('id')]
    public function newMethod(int $id): Response
    {
        // ...
    }
    
    // Legacy method still using annotation
    /**
     * @Hash("id")
     */
    public function legacyMethod(int $id): Response
    {
        // ...
    }
}
```

## Automated Migration Tools

### Using Rector

We provide Rector rules to automatically convert annotations to attributes:

```bash
# Install Rector if not already installed
composer require rector/rector --dev

# Run the migration
vendor/bin/rector process src/ --config=rector-annotations-to-attributes.php
```

### Manual Search and Replace

For simple cases, you can use your IDE's search and replace with regex:

- Search: `\* @Hash\("([^"]+)"\)`
- Replace: `#[Hash('$1')]`

## Troubleshooting

### Common Issues

#### 1. Attributes Not Working

**Symptom**: Routes are not being encoded/decoded after adding attributes

**Solution**: Ensure you're using PHP 8.0+ and have updated the import statement:
```php
use Pgs\HashIdBundle\Attribute\Hash; // NOT Annotation\Hash
```

#### 2. Deprecation Warnings

**Symptom**: Getting deprecation warnings for annotation usage

**Solution**: This is expected behavior. Either:
- Migrate to attributes (recommended)
- Temporarily disable warnings in configuration

#### 3. Both Annotation and Attribute Present

**Symptom**: Duplicate configuration warning

**Solution**: Remove the annotation, keep only the attribute:
```php
// Wrong - both present
/**
 * @Hash("id")
 */
#[Hash('id')]
public function show(int $id) {}

// Correct - only attribute
#[Hash('id')]
public function show(int $id) {}
```

## Compatibility Report

Generate a compatibility report for your controllers:

```php
use Pgs\HashIdBundle\Service\CompatibilityLayer;

$compatibilityLayer = $container->get(CompatibilityLayer::class);
$report = $compatibilityLayer->getCompatibilityReport(YourController::class);

// Shows which methods use annotations vs attributes
print_r($report);
```

## Future Enhancements

In future versions, we plan to support:

- Class-level attributes for applying Hash to all methods
- Custom hasher selection via attributes
- Conditional encoding based on attribute parameters

## Getting Help

If you encounter issues during migration:

1. Check this guide for common solutions
2. Review the [test examples](../tests/Functional/AttributeRoutingTest.php)
3. Open an issue on [GitHub](https://github.com/pgs/hashid-bundle/issues)

## Summary

Migrating from annotations to attributes is straightforward:

1. ✅ Ensure PHP 8.0+
2. ✅ Update import statements
3. ✅ Replace `@Hash` with `#[Hash]`
4. ✅ Test your application
5. ✅ Optional: Configure deprecation warnings

The bundle's dual support ensures a smooth transition period, allowing you to migrate at your own pace while maintaining full functionality.