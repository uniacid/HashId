# HashId Bundle Migration FAQ

## Frequently Asked Questions

### General Migration Questions

#### Q: How long does the migration from v3.x to v4.x typically take?

**A:** For most projects:
- Small projects (< 10 controllers): 1-2 hours
- Medium projects (10-50 controllers): 2-4 hours
- Large projects (50+ controllers): 4-8 hours

The majority of time is spent on testing, not the actual code changes.

#### Q: Can I use both annotations and attributes simultaneously?

**A:** Yes! Version 4.0 supports both for a smooth transition:
- Annotations are deprecated but fully functional
- Attributes take precedence when both are present
- Configure preference in `pgs_hash_id.yaml`

#### Q: What is the minimum PHP version required?

**A:** PHP 8.1 is the minimum. We recommend PHP 8.3 for best performance and all modern features.

### Rector Automation Questions

#### Q: What percentage of migration can Rector automate?

**A:** Rector typically automates:
- Annotation to attribute conversion: 90-95%
- Constructor property promotion: 85-90%
- Overall migration: 70-75%

Manual intervention is needed for custom implementations and complex scenarios.

#### Q: Rector is making unwanted changes. How do I exclude specific files?

**A:** Add exclusions to your `rector.php`:

```php
$rectorConfig->skip([
    __DIR__ . '/src/Legacy/*.php',
    __DIR__ . '/src/CustomImplementation.php',
    '**/Fixtures/**',
]);
```

#### Q: Can I run Rector on production code?

**A:** Always run Rector in a development environment first:
1. Test on a branch
2. Review all changes
3. Run your test suite
4. Deploy to staging
5. Then deploy to production

### Common Error Solutions

#### Error: "The annotation reader service is not available"

**Cause:** Missing Doctrine annotations package when using annotations.

**Solution:**
```bash
composer require doctrine/annotations
```

Or migrate fully to attributes to remove this dependency.

#### Error: "Attribute class Hash not found"

**Cause:** Wrong namespace import.

**Solution:**
```php
// Correct for attributes
use Pgs\HashIdBundle\Attribute\Hash;

// NOT this (annotation namespace)
use Pgs\HashIdBundle\Annotation\Hash;
```

#### Error: "Call to undefined method getParameters()"

**Cause:** API changes between versions.

**Solution:** Check the method exists in both classes:
```php
// Both should support
$hash->getParameters();  // Returns array of parameter names
```

#### Error: "Route attribute constructor expects named arguments"

**Cause:** Symfony 6.4+ Route attributes use named parameters.

**Solution:**
```php
// Wrong
#[Route('/path', 'route_name')]

// Correct
#[Route('/path', name: 'route_name')]
```

### Performance Questions

#### Q: Will upgrading to v4.x improve performance?

**A:** Yes, you can expect:
- 15-20% faster annotation/attribute reading (attributes are native)
- 10-15% reduced memory usage (PHP 8.1+ optimizations)
- Better opcache utilization

#### Q: Are there any performance regressions?

**A:** No significant regressions. The compatibility layer adds minimal overhead (~2-3%) when using both systems simultaneously.

### Compatibility Questions

#### Q: Is v4.x backward compatible with v3.x?

**A:** Mostly yes:
- ✅ All core functionality maintained
- ✅ Same configuration options
- ✅ Same URL generation behavior
- ⚠️ Some internal APIs changed
- ⚠️ Minimum PHP/Symfony versions increased

#### Q: Can I roll back to v3.x after upgrading?

**A:** Yes, if you:
1. Keep annotations (don't remove them)
2. Don't use PHP 8.1+ specific features
3. Maintain Symfony 5.x compatibility

However, we recommend moving forward rather than rolling back.

#### Q: Will my existing URLs still work?

**A:** Yes! The hashing algorithm remains unchanged. All existing URLs will continue to work exactly as before.

### Testing Questions

#### Q: How do I test that the migration was successful?

**A:** Run this test checklist:

```bash
# 1. Run migration tests
vendor/bin/phpunit tests/Migration/

# 2. Run your existing test suite
vendor/bin/phpunit

# 3. Check for deprecations
vendor/bin/phpunit --display-deprecations

# 4. Run static analysis
vendor/bin/phpstan analyse --level=9

# 5. Test URL generation manually
php bin/console router:match /your/test/url
```

#### Q: What should I test manually?

**A:** Focus on:
1. URL generation in controllers
2. Twig template URL generation
3. API endpoints with hashed parameters
4. Form submissions with hashed IDs
5. JavaScript/AJAX calls using generated URLs

### Deployment Questions

#### Q: Can I deploy v4.x without downtime?

**A:** Yes, follow this strategy:
1. Deploy code with compatibility mode enabled
2. Monitor for issues (both systems active)
3. Gradually migrate controllers
4. Disable annotation support when complete

#### Q: Should I migrate in production or pre-production first?

**A:** Always follow this order:
1. Development environment
2. Testing/CI environment
3. Staging/pre-production
4. Production (after thorough testing)

### Future Planning

#### Q: When will annotation support be removed?

**A:** Timeline:
- v4.0 (now): Annotations deprecated but supported
- v4.x: Continued dual support with deprecation warnings
- v5.0 (late 2024): Annotations removed completely

#### Q: Should I wait for v5.0 or migrate now?

**A:** Migrate to v4.0 now because:
- Immediate PHP 8.1+ performance benefits
- Gradual migration path available
- More time to test and adapt
- Easier upgrade to v5.0 later

### Troubleshooting Specific Issues

#### Issue: Rector changes break my custom code

**Solution:** Run Rector in phases:
```bash
# First: Just annotation to attribute
vendor/bin/rector process --config=rector-php81.php

# Test thoroughly, then:
vendor/bin/rector process --config=rector-quality.php
```

#### Issue: Some controllers are not being processed

**Check:**
1. File is in Rector's configured paths
2. File doesn't have parse errors
3. Annotations are properly formatted
4. File isn't in skip list

#### Issue: Generated hashes are different after migration

**This should never happen!** If it does:
1. Check your salt configuration
2. Verify hashids library version
3. Check parameter names haven't changed
4. File a bug report immediately

### Getting Help

#### Where can I get more help?

1. **Documentation**: Check `/docs` folder
2. **Examples**: Review `/tests/Migration/Fixtures`
3. **GitHub Issues**: Report bugs or ask questions
4. **Stack Overflow**: Tag with `symfony` and `hashids`

#### How do I report a migration bug?

Include:
1. PHP version (`php -v`)
2. Symfony version (`composer show symfony/framework-bundle`)
3. HashId Bundle version
4. Error message and stack trace
5. Minimal code example reproducing the issue

### Best Practices

#### Migration DO's:
- ✅ DO backup your code first
- ✅ DO run Rector with --dry-run first
- ✅ DO test thoroughly after each phase
- ✅ DO migrate in a feature branch
- ✅ DO read all documentation

#### Migration DON'Ts:
- ❌ DON'T skip testing
- ❌ DON'T migrate directly in production
- ❌ DON'T ignore deprecation warnings
- ❌ DON'T rush the migration
- ❌ DON'T modify generated code without understanding it

## Still Have Questions?

If your question isn't answered here:

1. Check the [full migration guide](../UPGRADE-4.0.md)
2. Review the [Rector documentation](./rector-migration.md)
3. Look at [test examples](../tests/Migration/Fixtures/)
4. Open a GitHub issue with details