# Rector Usage Guide

This document describes how to use Rector for automated PHP modernization in the HashId Bundle.

## Overview

The HashId Bundle uses a modular Rector configuration approach with separate config files for different modernization phases:

- `rector.php` - Main configuration that imports others selectively
- `rector-php81.php` - PHP 8.1 features (constructor promotion, match expressions, readonly properties)
- `rector-symfony.php` - Symfony 6.4 LTS compatibility rules  
- `rector-quality.php` - Code quality improvements and dead code removal
- `rector-php82.php` - PHP 8.2 features (placeholder for Phase 2)
- `rector-php83.php` - PHP 8.3 features (placeholder for Phase 2)

## Quick Start

### 1. Install Rector
```bash
composer install
# Note: May require dependency updates for PHP 8.1+ compatibility
```

### 2. Run Dry-Run Analysis
```bash
# Use the convenient dry-run script
./bin/rector-dry-run.sh

# Or run directly with vendor/bin/rector
vendor/bin/rector process --dry-run
```

### 3. Preview Specific Configurations
```bash
# PHP 8.1 features only
./bin/rector-dry-run.sh -c rector-php81.php

# Symfony compatibility only  
./bin/rector-dry-run.sh -c rector-symfony.php

# Code quality improvements only
./bin/rector-dry-run.sh -c rector-quality.php
```

### 4. Apply Changes (After Review)
```bash
# Apply all enabled rules
vendor/bin/rector process

# Apply specific configuration
vendor/bin/rector process --config=rector-php81.php
```

## Dry-Run Workflow

The recommended workflow for safe modernization:

1. **Analyze First**: Always run dry-run before applying changes
2. **Review Changes**: Carefully review all proposed modifications  
3. **Test Incrementally**: Apply one configuration at a time
4. **Run Tests**: Ensure all tests pass after each application
5. **Commit Atomically**: Make one commit per Rector configuration applied

### Dry-Run Script Options

```bash
./bin/rector-dry-run.sh [OPTIONS]

Options:
  -c, --config FILE      Rector configuration file to use
  -f, --format FORMAT    Output format: console, json
  -p, --path PATH        Specific path to analyze  
  -h, --help            Show help message

Examples:
  ./bin/rector-dry-run.sh                        # Main config
  ./bin/rector-dry-run.sh -c rector-php81.php    # PHP 8.1 only
  ./bin/rector-dry-run.sh -f json                # JSON output
  ./bin/rector-dry-run.sh -p src/Service         # Specific directory
```

## Incremental Application Strategy

### Phase 1 (Current): Foundation Setup
1. Apply `rector-quality.php` first (safest, removes dead code)
2. Apply `rector-php81.php` second (constructor promotion, match expressions)
3. Apply `rector-symfony.php` third (framework compatibility)

### Phase 2 (Future): Advanced Features  
1. Enable `rector-php82.php` (readonly classes, standalone types)
2. Enable `rector-php83.php` (typed constants, override attribute)

## Configuration Details

### Main Configuration (`rector.php`)
The main config imports others selectively. Enable/disable specific configs by uncommenting the imports:

```php
// Uncomment to apply:
// $rectorConfig->import(__DIR__ . '/rector-php81.php');
// $rectorConfig->import(__DIR__ . '/rector-symfony.php');  
// $rectorConfig->import(__DIR__ . '/rector-quality.php');
```

### Skip Patterns
Each config includes skip patterns for:
- Test fixtures (`tests/Fixtures/`)
- Vendor code (`vendor/`)
- Generated files (`var/`)
- Specific rules that need manual attention

### Parallel Processing
All configs enable parallel processing for faster execution:
```php
$rectorConfig->parallel();
```

## Testing Integration

After applying Rector changes:

```bash
# Run full test suite
vendor/bin/phpunit

# Run static analysis
vendor/bin/phpstan analyse

# Check code standards
vendor/bin/php-cs-fixer fix --dry-run
```

## Troubleshooting

### Common Issues

1. **Dependencies conflict**: Update composer.json for PHP 8.1+ compatibility
2. **Configuration errors**: Run `php -l rector-*.php` to check syntax  
3. **Skip patterns**: Add problematic files/rules to skip arrays
4. **Memory issues**: Increase PHP memory limit for large codebases

### Getting Help

```bash
vendor/bin/rector --help
vendor/bin/rector list
./bin/rector-dry-run.sh --help
```

## Best Practices

1. **Always dry-run first** - Never apply Rector blindly
2. **One config at a time** - Apply incrementally for easier debugging
3. **Test after each application** - Ensure no regressions introduced
4. **Review changes carefully** - Understand each transformation
5. **Atomic commits** - One commit per Rector configuration applied
6. **Document skip patterns** - Explain why certain rules are skipped

## Integration with CI/CD

Add Rector validation to your CI pipeline:

```yaml
- name: Check Rector rules
  run: vendor/bin/rector process --dry-run --ansi
```

This ensures no unexpected changes slip through and configurations remain valid.