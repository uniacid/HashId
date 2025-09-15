# Rector Migration Guide for HashId Bundle

## Overview

Rector is a powerful tool that automates PHP code upgrades and refactoring. This guide explains how to use Rector to migrate from HashId Bundle v3.x to v4.x, achieving 70%+ automation in the migration process.

## Installation

### Adding Rector to Your Project

```bash
composer require rector/rector --dev
```

### Verifying Installation

```bash
vendor/bin/rector --version
```

## Configuration Structure

The HashId Bundle provides multiple Rector configuration files for different migration phases:

```
.
├── rector.php                 # Main configuration file
├── rector-php81.php          # PHP 8.1 upgrade rules
├── rector-php82.php          # PHP 8.2 upgrade rules
├── rector-php83.php          # PHP 8.3 upgrade rules
├── rector-symfony.php        # Symfony 6.4/7.0 rules
├── rector-quality.php        # Code quality improvements
├── rector-compatibility.php  # Compatibility layer rules
└── rector-rules/             # Custom HashId-specific rules
    └── DeprecationHandler.php
```

## Step-by-Step Migration Process

### Step 1: Analyze Your Codebase

First, run Rector in dry-run mode to see what changes will be made:

```bash
# Preview all changes without modifying files
vendor/bin/rector process --config=rector.php --dry-run

# Generate a detailed report
vendor/bin/rector process --config=rector.php --dry-run --output-format=json > rector-report.json
```

### Step 2: Apply PHP 8.1 Transformations

This is the primary migration step that converts annotations to attributes:

```bash
# Preview PHP 8.1 changes
vendor/bin/rector process --config=rector-php81.php --dry-run

# Apply the changes
vendor/bin/rector process --config=rector-php81.php

# Review the changes
git diff
```

### Step 3: Progressive Enhancement (Optional)

Apply additional modernizations in phases:

```bash
# PHP 8.2 features (readonly properties, etc.)
vendor/bin/rector process --config=rector-php82.php

# PHP 8.3 features (typed constants, etc.)
vendor/bin/rector process --config=rector-php83.php

# Symfony 6.4/7.0 compatibility
vendor/bin/rector process --config=rector-symfony.php

# Code quality improvements
vendor/bin/rector process --config=rector-quality.php
```

## What Rector Automates

### Annotation to Attribute Conversion

**Before:**
```php
use Pgs\HashIdBundle\Annotation\Hash;
use Symfony\Component\Routing\Annotation\Route;

class UserController
{
    /**
     * @Route("/user/{id}", name="user_show")
     * @Hash("id")
     */
    public function show(int $id) { }
}
```

**After:**
```php
use Pgs\HashIdBundle\Attribute\Hash;
use Symfony\Component\Routing\Attribute\Route;

class UserController
{
    #[Route('/user/{id}', name: 'user_show')]
    #[Hash('id')]
    public function show(int $id) { }
}
```

### Constructor Property Promotion

**Before:**
```php
class HashService
{
    private HashidsConverter $converter;
    private array $config;

    public function __construct(HashidsConverter $converter, array $config)
    {
        $this->converter = $converter;
        $this->config = $config;
    }
}
```

**After:**
```php
class HashService
{
    public function __construct(
        private HashidsConverter $converter,
        private array $config
    ) {
    }
}
```

### Readonly Properties

**Before:**
```php
class HashConfiguration
{
    private string $salt;

    public function __construct(string $salt)
    {
        $this->salt = $salt;
    }

    public function getSalt(): string
    {
        return $this->salt;
    }
}
```

**After:**
```php
class HashConfiguration
{
    public function __construct(
        public readonly string $salt
    ) {
    }
}
```

## Custom Rules for HashId Bundle

The bundle includes custom Rector rules in `rector-rules/`:

### DeprecationHandler

Automatically adds deprecation notices when using old annotation classes:

```php
// Rector automatically adds:
@trigger_error('The @Hash annotation is deprecated, use #[Hash] attribute instead.', E_USER_DEPRECATED);
```

## Command-Line Options

### Useful Rector Commands

```bash
# Process specific directories
vendor/bin/rector process src/Controller --config=rector-php81.php

# Process specific files
vendor/bin/rector process src/Controller/UserController.php

# Show diffs in color
vendor/bin/rector process --config=rector.php --dry-run --diff

# Clear cache if rules have changed
vendor/bin/rector process --clear-cache

# Use parallel processing for large codebases
vendor/bin/rector process --parallel

# Limit memory usage
vendor/bin/rector process --memory-limit=256M
```

### Output Formats

```bash
# Console output (default)
vendor/bin/rector process --dry-run

# JSON format for CI/CD pipelines
vendor/bin/rector process --dry-run --output-format=json

# GitHub Actions annotations
vendor/bin/rector process --dry-run --output-format=github
```

## Metrics and Reporting

### Collecting Migration Metrics

```bash
# Generate metrics report
vendor/bin/rector process --dry-run --output-format=json > metrics.json

# Parse metrics with jq
cat metrics.json | jq '.files | length'  # Count of files processed
cat metrics.json | jq '.diff_count'       # Number of changes
```

### Sample Metrics Report

```json
{
  "files": 42,
  "diff_count": 156,
  "applied_rules": [
    "AnnotationToAttributeRector: 78 changes",
    "PropertyPromotionRector: 34 changes",
    "ReadonlyPropertyRector: 44 changes"
  ],
  "automation_rate": "73%"
}
```

## Troubleshooting Rector Issues

### Common Problems and Solutions

#### 1. Memory Exhaustion

```bash
# Increase memory limit
vendor/bin/rector process --memory-limit=512M

# Process in smaller batches
vendor/bin/rector process src/Controller
vendor/bin/rector process src/Service
```

#### 2. Parse Errors

```bash
# Skip problematic files
# Add to rector.php:
$rectorConfig->skip([
    __DIR__ . '/src/Legacy/ProblematicFile.php',
]);
```

#### 3. Conflicting Rules

```bash
# Run rules in specific order
vendor/bin/rector process --config=rector-php81.php
vendor/bin/rector process --config=rector-quality.php
```

#### 4. Unexpected Changes

```bash
# Use dry-run and review changes
vendor/bin/rector process --dry-run --diff > changes.diff

# Revert if needed
git checkout -- .
```

## Integration with CI/CD

### GitHub Actions Example

```yaml
name: Rector Check
on: [pull_request]
jobs:
  rector:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - run: composer install
      - run: vendor/bin/rector process --dry-run --output-format=github
```

### GitLab CI Example

```yaml
rector:
  stage: test
  script:
    - composer install
    - vendor/bin/rector process --dry-run
  allow_failure: true
```

## Best Practices

1. **Always use dry-run first** - Review changes before applying
2. **Commit before running Rector** - Easy rollback if needed
3. **Run tests after each phase** - Ensure functionality is preserved
4. **Apply rules progressively** - Don't apply all rules at once
5. **Review git diff carefully** - Rector may make unexpected changes
6. **Keep Rector updated** - New versions include bug fixes and improvements

## Next Steps

After successful Rector migration:

1. Run your test suite
2. Review all changes with `git diff`
3. Update any manual migration items
4. Test in staging environment
5. Document any custom changes made

## Resources

- [Rector Documentation](https://getrector.org/documentation)
- [HashId Bundle Custom Rules](../rector-rules/README.md)
- [Migration Examples](../tests/Migration/Fixtures/)
- [Troubleshooting Guide](./migration-faq.md)