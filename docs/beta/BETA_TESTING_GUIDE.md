# HashId Bundle v4.0 Beta Testing Guide

## Overview

Welcome to the HashId Bundle v4.0 beta testing program! This guide will help you migrate your existing HashId v3.x installation to v4.0 using our automated Rector-based migration tools.

Our goal is to validate that the migration process achieves **70%+ automation** while maintaining backward compatibility and improving performance.

## Prerequisites

Before starting the beta test:

- **PHP Version**: 8.1, 8.2, or 8.3
- **Symfony Version**: 6.4 LTS or 7.0
- **Existing HashId**: v3.x installed and configured
- **Composer**: 2.0 or higher
- **Git**: For version control during migration

## Quick Start

### Step 1: Backup Your Project

```bash
# Create a backup branch
git checkout -b hashid-v4-beta-test
git add .
git commit -m "Backup before HashId v4.0 beta migration"
```

### Step 2: Update Dependencies

```bash
# Update to beta version
composer require pgs/hashid-bundle:"4.0.0-beta1" --no-update
composer update pgs/hashid-bundle --with-dependencies
```

### Step 3: Run Automated Migration

```bash
# Install Rector if not already installed
composer require rector/rector --dev

# Run the HashId migration rector
vendor/bin/rector process --config=vendor/pgs/hashid-bundle/rector-configs/beta/rector-php81-beta.php

# For PHP 8.2+
vendor/bin/rector process --config=vendor/pgs/hashid-bundle/rector-configs/beta/rector-php82-beta.php

# For PHP 8.3+
vendor/bin/rector process --config=vendor/pgs/hashid-bundle/rector-configs/beta/rector-php83-beta.php
```

### Step 4: Review Changes

```bash
# Check what was changed
git diff

# Run tests to ensure everything works
./vendor/bin/phpunit

# Run static analysis
./vendor/bin/phpstan analyse --level=9
```

## Migration Checklist

### Automated Changes (Should be done by Rector)

- [ ] Annotations converted to PHP 8 attributes
  - `@Hash("id")` → `#[Hash("id")]`
- [ ] Constructor property promotion applied
- [ ] Readonly properties added where applicable
- [ ] Typed class constants (PHP 8.3)
- [ ] Return type declarations added
- [ ] Strict types declaration added

### Manual Review Required

- [ ] Custom annotation extensions
- [ ] Complex route configurations
- [ ] Custom parameter processors
- [ ] Doctrine integration points
- [ ] Twig template updates (if using custom filters)

## Testing Your Migration

### 1. Functional Tests

Run your existing test suite:

```bash
# Run all tests
./vendor/bin/phpunit

# Run HashId specific tests
./vendor/bin/phpunit --filter HashId
```

### 2. Performance Benchmarks

Test encoding/decoding performance:

```php
use Pgs\HashIdBundle\Service\HashidsConverter;

$converter = $container->get(HashidsConverter::class);

// Benchmark encoding
$start = microtime(true);
for ($i = 0; $i < 10000; $i++) {
    $hash = $converter->encode($i);
}
$encodingTime = microtime(true) - $start;

// Benchmark decoding
$start = microtime(true);
for ($i = 0; $i < 10000; $i++) {
    $id = $converter->decode("hash_$i");
}
$decodingTime = microtime(true) - $start;

echo "Encoding: {$encodingTime}s for 10000 operations\n";
echo "Decoding: {$decodingTime}s for 10000 operations\n";
```

### 3. Route Generation Tests

Verify that route generation still works:

```php
// In your controller or test
$url = $this->generateUrl('app_show', ['id' => 123]);
// Should produce: /show/4w9aA11avM (or similar hash)
```

### 4. Security Validation

Ensure URL obfuscation is working:

```bash
# These should produce different hashes
curl https://yourapp.test/user/1
curl https://yourapp.test/user/2
curl https://yourapp.test/user/3
```

## Collecting Metrics

After migration, please run our metrics collector:

```bash
# Generate migration metrics
php vendor/pgs/hashid-bundle/scripts/collect-beta-metrics.php

# This will output:
# - Automation percentage achieved
# - Number of manual interventions required
# - Migration time
# - Error count
```

## Reporting Issues

### For Rector Automation Issues

Use our [Rector Automation Report template](https://github.com/pgs-soft/hashid-bundle/issues/new?template=rector-automation-report.yml)

Include:
- Original code that failed to convert
- Expected result
- Actual result
- PHP and Symfony versions

### For Runtime Issues

Use our [Migration Issue template](https://github.com/pgs-soft/hashid-bundle/issues/new?template=migration-issue.yml)

Include:
- Error message and stack trace
- Steps to reproduce
- Your configuration

### For Performance Regressions

Use our [Beta Feedback template](https://github.com/pgs-soft/hashid-bundle/issues/new?template=beta-feedback.yml)

Include:
- Performance metrics before/after
- Your environment details
- Specific operations affected

## Success Metrics

We're tracking:

1. **Automation Rate**: Target 70%+ automated conversion
2. **Migration Time**: Target 50%+ faster than manual
3. **Error Reduction**: Target 80%+ fewer errors than manual migration
4. **Performance**: 30%+ improvement in encoding/decoding
5. **Compatibility**: 100% backward compatibility for public APIs

## Docker Test Environment

For isolated testing, use our Docker environment:

```bash
# Clone the beta testing repository
git clone https://github.com/pgs-soft/hashid-bundle-beta-test.git
cd hashid-bundle-beta-test

# Start containers (PHP 8.1, 8.2, 8.3)
docker-compose up -d

# Run migration in each environment
docker-compose exec php81 vendor/bin/rector process
docker-compose exec php82 vendor/bin/rector process
docker-compose exec php83 vendor/bin/rector process

# Compare results
docker-compose exec php81 vendor/bin/phpunit
docker-compose exec php82 vendor/bin/phpunit
docker-compose exec php83 vendor/bin/phpunit
```

## FAQ

### Q: Will v4.0 break my existing code?

A: No, v4.0 maintains backward compatibility. Both annotations and attributes are supported in v4.0, with annotations marked as deprecated.

### Q: How long does the migration take?

A: With Rector automation, most projects complete migration in under 5 minutes. Manual review typically takes 15-30 minutes.

### Q: What if Rector can't convert something?

A: Rector will skip complex cases and add a `// TODO: Manual migration required` comment. These require manual intervention.

### Q: Can I roll back if something goes wrong?

A: Yes, simply revert your git changes and downgrade to v3.x via Composer.

## Support

- **Documentation**: [HashId v4.0 Docs](https://github.com/pgs-soft/hashid-bundle/blob/4.0/README.md)
- **Discord**: [Join our beta testing channel](#)
- **Email**: hashid-beta@pgs-soft.com
- **GitHub Discussions**: [Beta Testing Forum](https://github.com/pgs-soft/hashid-bundle/discussions)

## Thank You!

Your participation in the beta program helps ensure HashId v4.0 is production-ready and provides a smooth migration path for the entire Symfony community.

Please complete our [feedback survey](https://forms.gle/hashid-v4-beta-feedback) after testing!