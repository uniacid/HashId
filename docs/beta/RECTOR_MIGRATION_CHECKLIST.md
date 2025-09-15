# Rector Migration Checklist for HashId v4.0

## Pre-Migration Verification

### Environment Check
- [ ] PHP version is 8.1 or higher
- [ ] Symfony version is 6.4 or 7.0
- [ ] Rector is installed (version 1.0+)
- [ ] Project has git backup/branch
- [ ] All tests pass on v3.x

### Current State Analysis
- [ ] Count of `@Hash` annotations: _____
- [ ] Count of annotation-based controllers: _____
- [ ] Count of custom parameter processors: _____
- [ ] Current PHPStan level: _____
- [ ] Current test coverage: _____%

## Automated Migration Steps

### Phase 1: Annotation to Attribute Conversion

#### Run Rector
```bash
vendor/bin/rector process --config=rector-php81-beta.php --dry-run
```

#### Expected Conversions
- [ ] `@Hash` → `#[Hash]`
- [ ] `use Annotation\Hash` → `use Attribute\Hash`
- [ ] Multiple annotations → Multiple attributes
- [ ] Annotation arrays → Attribute arrays

#### Verification
- [ ] No syntax errors after conversion
- [ ] Attribute imports added correctly
- [ ] Original functionality preserved

### Phase 2: PHP 8.1 Features

#### Constructor Property Promotion
- [ ] Simple properties promoted
- [ ] Typed properties maintained
- [ ] Visibility preserved
- [ ] Default values kept

Before:
```php
private string $salt;
public function __construct(string $salt) {
    $this->salt = $salt;
}
```

After:
```php
public function __construct(
    private string $salt
) {}
```

#### Readonly Properties
- [ ] Immutable properties marked readonly
- [ ] Configuration objects updated
- [ ] DTO properties converted

### Phase 3: PHP 8.2 Features (if applicable)

#### DNF Types
- [ ] Complex type declarations updated
- [ ] Nullable unions simplified

#### Readonly Classes
- [ ] DTOs marked as readonly classes
- [ ] Value objects converted

### Phase 4: PHP 8.3 Features (if applicable)

#### Typed Class Constants
- [ ] Configuration constants typed
- [ ] Interface constants typed

Before:
```php
public const ALPHABET = 'abcdefghijklmnopqrstuvwxyz';
```

After:
```php
public const string ALPHABET = 'abcdefghijklmnopqrstuvwxyz';
```

#### JSON Validate
- [ ] `json_decode` + error checking → `json_validate`

## Manual Migration Tasks

### Complex Annotations
- [ ] Review multi-line annotations
- [ ] Check nested annotation structures
- [ ] Verify annotation inheritance

### Custom Extensions
- [ ] Update custom annotation classes
- [ ] Convert annotation readers to attribute readers
- [ ] Update dependency injection

### Route Configurations
- [ ] Verify route parameter encoding
- [ ] Check custom route loaders
- [ ] Test internationalized routes

### Doctrine Integration
- [ ] ParamConverter compatibility
- [ ] Entity listener updates
- [ ] Query parameter handling

## Post-Migration Validation

### Code Quality
- [ ] Run PHPStan level 9
- [ ] Run PHP CS Fixer
- [ ] Check for deprecation warnings
- [ ] Verify no `@` error suppression added

### Functional Testing
- [ ] All unit tests pass
- [ ] All integration tests pass
- [ ] Manual smoke testing completed
- [ ] Performance benchmarks run

### Security Validation
- [ ] URL obfuscation working
- [ ] No exposed integer IDs
- [ ] Salt configuration correct
- [ ] Input validation functioning

## Metrics Collection

### Automation Metrics
- [ ] Total files processed: _____
- [ ] Files auto-migrated: _____
- [ ] Manual interventions: _____
- [ ] Automation percentage: _____%

### Time Metrics
- [ ] Rector execution time: _____
- [ ] Manual fix time: _____
- [ ] Total migration time: _____
- [ ] Time saved vs manual: _____%

### Error Metrics
- [ ] Rector errors encountered: _____
- [ ] Manual fixes required: _____
- [ ] Test failures after migration: _____
- [ ] Production issues found: _____

## Rollback Plan

### If Issues Occur
- [ ] Document the issue with details
- [ ] Revert git changes
- [ ] Restore composer.lock
- [ ] Downgrade to v3.x
- [ ] Report issue to beta team

### Issue Reporting
- [ ] Screenshot/copy of error
- [ ] Original code snippet
- [ ] Expected conversion
- [ ] Actual result
- [ ] Environment details

## Sign-off

### Developer Verification
- [ ] Migration completed successfully
- [ ] All tests passing
- [ ] Performance acceptable
- [ ] No breaking changes found

**Developer Name**: _________________
**Date**: _________________
**Time Taken**: _________________
**Automation %**: _________________

### Notes
_Space for additional observations, issues, or feedback:_

---

## Feedback Submission

After completing this checklist, please:

1. Submit metrics via: `php vendor/pgs/hashid-bundle/scripts/submit-metrics.php`
2. Complete survey at: https://forms.gle/hashid-v4-beta
3. Report issues at: https://github.com/pgs-soft/hashid-bundle/issues

Thank you for participating in the HashId v4.0 beta program!