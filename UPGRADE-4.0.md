# Upgrade Guide to HashId Bundle 4.0

This guide covers upgrading from HashId Bundle 3.x to 4.0, which introduces modern PHP 8.1+ features and Symfony 6.4+ compatibility.

## Table of Contents

- [Requirements](#requirements)
- [Prerequisites Checklist](#prerequisites-checklist)
- [Breaking Changes](#breaking-changes)
- [Migration Path](#migration-path)
- [Rector Automation](#rector-automation)
- [Manual Migration Steps](#manual-migration-steps)
- [Configuration Updates](#configuration-updates)
- [Testing Your Migration](#testing-your-migration)
- [Troubleshooting](#troubleshooting)
- [Migration Checklist](#migration-checklist)
- [Version Compatibility Matrix](#version-compatibility-matrix)

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

## Prerequisites Checklist

Before starting the migration, ensure you have:

- [ ] Backed up your current project
- [ ] Reviewed all breaking changes
- [ ] Tested your application with current version
- [ ] Identified all controllers using HashId annotations
- [ ] Prepared a test environment for migration
- [ ] Allocated time for testing (estimated: 2-4 hours for medium projects)

## Version Compatibility Matrix

| HashId Bundle | PHP Version | Symfony Version | Hashids Library |
|--------------|-------------|-----------------|-----------------|
| 3.x          | 7.2 - 8.0   | 4.4, 5.x        | ^3.0 \|\| ^4.0  |
| 4.0          | 8.1 - 8.3   | 6.4 LTS, 7.0    | ^4.0 \|\| ^5.0  |
| 5.0 (future) | 8.2+        | 7.0+            | ^5.0            |

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

#### Option B: Full Automated Migration

Use Rector to automatically convert all annotations to attributes (see [Rector Automation](#rector-automation) section).

## Rector Automation

### Installing Rector

```bash
# If not already installed
composer require rector/rector --dev
```

### Using Rector for Migration

```bash
# Step 1: Preview changes (dry run)
vendor/bin/rector process --config=rector-php81.php --dry-run

# Step 2: Apply transformations
vendor/bin/rector process --config=rector-php81.php

# Step 3: Apply additional modernizations (optional)
vendor/bin/rector process --config=rector-quality.php
```

### Available Rector Configurations

| Configuration File | Purpose | Automation Level |
|-------------------|---------|------------------|
| `rector-php81.php` | Converts annotations to attributes | 90% |
| `rector-php82.php` | Applies PHP 8.2 features | 70% |
| `rector-php83.php` | Applies PHP 8.3 features | 60% |
| `rector-symfony.php` | Symfony 6.4/7.0 compatibility | 80% |
| `rector-quality.php` | Code quality improvements | 75% |

### Custom Rector Rules

The bundle includes custom Rector rules in `rector-rules/` directory:
- `DeprecationHandler.php` - Manages deprecation notices during migration

## Manual Migration Steps

Some changes require manual intervention:

### 1. Service Configuration

If you have custom service definitions:

```yaml
# Before (services.yaml)
services:
    my_custom_processor:
        class: App\Service\CustomHashProcessor
        arguments:
            - '@pgs_hash_id.parameters_processor'

# After (services.yaml)
services:
    my_custom_processor:
        class: App\Service\CustomHashProcessor
        arguments:
            - '@Pgs\HashIdBundle\Service\ParametersProcessor'
```

### 2. Custom Annotation Readers

If you've extended the annotation system:

```php
// Before
use Doctrine\Common\Annotations\Reader;

class CustomReader
{
    private Reader $reader;
    
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }
}

// After
use Pgs\HashIdBundle\Service\AttributeReader;

class CustomReader
{
    private AttributeReader $reader;
    
    public function __construct(AttributeReader $reader)
    {
        $this->reader = $reader;
    }
}
```

### 3. Event Subscribers

Update any custom event subscribers:

```php
// Check for method signature changes
public function onKernelController(ControllerEvent $event): void
{
    // Implementation remains the same
}
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

## Configuration Updates

### Bundle Configuration

Update your bundle configuration:

```yaml
# config/packages/pgs_hash_id.yaml
pgs_hash_id:
    # Core settings (unchanged)
    salt: '%env(HASHID_SALT)%'
    min_hash_length: 10
    alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
    
    # New v4.0 compatibility settings
    compatibility:
        suppress_deprecations: true  # Disables deprecation warnings during migration
        prefer_attributes: true       # Uses attributes when both are present
        legacy_mode: false           # Set to true to force annotation-only mode

```

### Environment Variables

```bash
# .env
HASHID_SALT=your-secret-salt-value-here
```

## Testing Your Migration

### Step 1: Update Tests

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

### Step 2: Run Migration Tests

```bash
# Run the migration test suite
vendor/bin/phpunit tests/Migration/

# Run compatibility tests
vendor/bin/phpunit tests/Migration/CompatibilityTest.php

# Test Rector transformations
vendor/bin/phpunit tests/Migration/RectorTransformationTest.php
```

### Step 3: Verify Functionality

```bash
# Run full test suite
vendor/bin/phpunit

# Check code coverage
vendor/bin/phpunit --coverage-html coverage/

# Run static analysis
vendor/bin/phpstan analyse --level=9 src/
```

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