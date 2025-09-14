# Upgrade Guide to HashId Bundle 4.0

This guide covers upgrading from HashId Bundle 3.x to 4.0, which introduces modern PHP 8.1+ features and Symfony 6.4+ compatibility.

## Requirements

### Minimum Requirements
- PHP 8.1 or higher (8.3 recommended)
- Symfony 6.4 LTS or 7.0
- hashids/hashids ^4.0 or ^5.0

### Development Requirements
- PHPUnit 10.x
- PHPStan level 9
- PHP CS Fixer 3.40+
- Rector 1.0+

## Breaking Changes

### 1. PHP Version Requirement
- **Before**: PHP 7.2+
- **After**: PHP 8.1+
- **Action**: Upgrade your PHP version to at least 8.1

### 2. Symfony Version Requirement
- **Before**: Symfony 4.4, 5.x
- **After**: Symfony 6.4 LTS or 7.0
- **Action**: Upgrade Symfony to 6.4 or 7.0

### 3. Removed Dependencies
- **Removed**: `doctrine/annotations` (if using attributes)
- **Action**: Remove from your `composer.json` if not needed elsewhere

## Migration Path

### Step 1: Update Dependencies

```bash
composer require "pgs-soft/hashid-bundle:^4.0"
```

### Step 2: Choose Your Migration Strategy

#### Option A: Gradual Migration (Recommended)

Keep both annotations and attributes during transition:

```php
// Both work in v4.0
use Pgs\HashIdBundle\Annotation\Hash;
use Pgs\HashIdBundle\Attribute\Hash as HashAttribute;

class MyController
{
    /**
     * @Route("/demo/{id}")
     * @Hash("id")  // Still works, but deprecated
     */
    public function withAnnotation(int $id) { }
    
    #[Route('/demo/{id}')]
    #[HashAttribute('id')]  // Recommended
    public function withAttribute(int $id) { }
}
```

#### Option B: Full Migration

Use Rector to automatically convert all annotations to attributes:

```bash
# Dry run to preview changes
vendor/bin/rector process --config=rector.php --dry-run

# Apply transformations
vendor/bin/rector process --config=rector.php
```

### Step 3: Update Your Code

#### Before (Annotations)
```php
<?php

namespace App\Controller;

use Pgs\HashIdBundle\Annotation\Hash;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class ApiController
{
    /**
     * @Route("/user/{id}")
     * @Hash("id")
     */
    public function getUser(int $id) { }
    
    /**
     * @Route("/users/{id}/{otherId}")
     * @Hash({"id", "otherId"})
     */
    public function compareUsers(int $id, int $otherId) { }
}
```

#### After (Attributes)
```php
<?php

namespace App\Controller;

use Pgs\HashIdBundle\Attribute\Hash;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ApiController
{
    #[Route('/user/{id}')]
    #[Hash('id')]
    public function getUser(int $id) { }
    
    #[Route('/users/{id}/{otherId}')]
    #[Hash(['id', 'otherId'])]
    public function compareUsers(int $id, int $otherId) { }
}
```

### Step 4: Configuration Updates

#### Suppress Deprecation Warnings (Optional)

If you need more time to migrate, you can suppress deprecation warnings:

```yaml
# config/packages/pgs_hash_id.yaml
pgs_hash_id:
    compatibility:
        suppress_deprecations: true  # Disables deprecation warnings
        prefer_attributes: true       # Uses attributes when both are present
```

### Step 5: Update Tests

#### PHPUnit Configuration

Update your `phpunit.xml.dist`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         executionOrder="depends,defects"
         beStrictAboutOutputDuringTests="true"
         failOnRisky="true"
         failOnWarning="true">
    <!-- Update to PHPUnit 10.x schema -->
</phpunit>
```

#### Test Updates

Update your tests to use the new attribute syntax if testing controllers directly.

## New Features in 4.0

### PHP 8.1+ Features
- Constructor property promotion in service classes
- Union types for flexible parameter handling
- Readonly properties for immutable configurations
- Match expressions for cleaner conditionals

### Improved Developer Experience
- Rector rules for automated modernization
- PHPStan level 9 for better type safety
- Modern PHP CS Fixer rules
- Comprehensive fixture-based testing

## Deprecation Timeline

| Feature | Deprecated In | Removed In | Replacement |
|---------|--------------|------------|-------------|
| @Hash annotation | 4.0 | 5.0 | #[Hash] attribute |
| @Route annotation | 4.0 | 5.0 | #[Route] attribute |
| PHP 8.0 support | 4.0 | 5.0 | PHP 8.2+ |
| Symfony 6.3 support | 4.0 | 5.0 | Symfony 7.0+ |

## Troubleshooting

### Common Issues

#### 1. Annotation Reader Not Found
**Error**: `The annotation reader service is not available`
**Solution**: Install `doctrine/annotations` if still using annotations:
```bash
composer require doctrine/annotations
```

#### 2. Attribute Not Recognized
**Error**: `Attribute class "Hash" not found`
**Solution**: Use the correct namespace:
```php
use Pgs\HashIdBundle\Attribute\Hash;  // For attributes
use Pgs\HashIdBundle\Annotation\Hash; // For annotations
```

#### 3. Deprecation Warnings
**Issue**: Too many deprecation warnings during migration
**Solution**: Temporarily suppress warnings in configuration (see Step 4)

### Getting Help

- Check the [examples](examples/) directory for migration examples
- Review the [test fixtures](tests/Rector/Fixtures/) for transformation patterns
- Open an issue on GitHub for specific problems

## Migration Checklist

- [ ] Upgrade PHP to 8.1+
- [ ] Upgrade Symfony to 6.4 or 7.0
- [ ] Update composer dependencies
- [ ] Choose migration strategy (gradual or full)
- [ ] Run Rector for automated transformations
- [ ] Update custom code manually if needed
- [ ] Update configuration files
- [ ] Run tests and fix any failures
- [ ] Test in staging environment
- [ ] Deploy to production

## Next Steps

After successfully upgrading to 4.0:

1. Plan for full attribute adoption before 5.0
2. Enable PHPStan level 9 checking
3. Apply PHP CS Fixer rules
4. Consider implementing custom Rector rules for your patterns

## Version 5.0 Preview

Version 5.0 (planned for late 2024) will:
- Remove all annotation support
- Require PHP 8.2+
- Full Symfony 7.0 support
- Enhanced performance optimizations
- Simplified codebase without compatibility layers

Start planning your full migration to attributes to be ready for 5.0!