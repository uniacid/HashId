# CI/CD Pipeline Setup

This document describes the GitHub Actions CI/CD pipeline configuration for the HashId Bundle modernization project.

## Overview

The CI/CD pipeline consists of two main workflows:
- **Main CI** (`ci.yml`) - Comprehensive testing and quality checks
- **Rector Validation** (`rector.yml`) - Modernization tool validation

## Main CI Workflow (ci.yml)

### Matrix Testing

Tests across multiple PHP and Symfony versions:
- **PHP Versions**: 8.1, 8.2, 8.3
- **Symfony Versions**: 6.4.*, 7.0.*
- **Total Combinations**: 5 (excluding unsupported combinations)

### Jobs

#### 1. Tests Job
- Runs PHPUnit test suite across all matrix combinations
- Generates code coverage reports
- Uploads coverage to Codecov
- Validates composer.json and PSR-4 autoloading

#### 2. Quality Checks Job
- **PHPStan**: Level 9 static analysis
- **PHP CS Fixer**: PSR-12 compliance checking
- **Syntax Check**: PHP syntax validation
- **Security Check**: Symfony security checker

#### 3. Backward Compatibility Job
- Checks for BC breaks using Roave BC Check (if installed)
- Runs on all pushes and PRs

#### 4. Mutation Testing Job
- Runs on pull requests only
- Uses Infection for mutation testing
- Minimum MSI: 80%, Covered MSI: 90%

### Quality Gates

All jobs must pass for the workflow to succeed:

| Check | Tool | Threshold | Failure Condition |
|-------|------|-----------|-------------------|
| Static Analysis | PHPStan | Level 9 | Any error |
| Code Style | PHP CS Fixer | PSR-12 | Any violation |
| Test Coverage | PHPUnit | 90% | Below threshold |
| Mutation Score | Infection | 80% MSI, 90% Covered MSI | Below threshold |
| Security | Security Checker | - | Any vulnerability |

## Rector Validation Workflow (rector.yml)

### Jobs

#### 1. Rector Configuration Validation
- Validates all 5 Rector configuration files load correctly
- Tests dry-run execution for each configuration
- Handles placeholder configurations gracefully

#### 2. Rector Dry Run Analysis
- Runs each Rector configuration separately
- Generates change preview diffs
- Matrix strategy for parallel execution

#### 3. Rector Regression Check
- Compares Rector output between base and PR branches
- Detects unintended changes in modernization rules
- Runs only on pull requests

#### 4. Performance Impact Assessment
- Measures Rector execution time for each configuration
- Helps track performance impact of rule changes
- Simple timing-based benchmarks

### Rector Configurations Tested

1. `rector.php` - Main entry point configuration
2. `rector-php81.php` - PHP 8.1 feature modernization
3. `rector-symfony.php` - Symfony framework modernization
4. `rector-quality.php` - Code quality improvements
5. `rector-php82.php` - PHP 8.2 features (placeholder)
6. `rector-php83.php` - PHP 8.3 features (placeholder)

## Branch Protection Configuration

### Recommended GitHub Settings

#### Protected Branches
- `master` (production)
- `dev/modernization-v4` (main development)

#### Branch Protection Rules

```yaml
# Master Branch Protection
required_status_checks:
  strict: true
  contexts:
    - "Tests PHP 8.1, Symfony 6.4.*"
    - "Tests PHP 8.2, Symfony 6.4.*"
    - "Tests PHP 8.3, Symfony 6.4.*"
    - "Tests PHP 8.2, Symfony 7.0.*"
    - "Tests PHP 8.3, Symfony 7.0.*"
    - "Code Quality Checks"
    - "Backward Compatibility Check"
    - "Rector Configuration Validation"
    - "Rector Dry Run Analysis (rector-php81.php)"
    - "Rector Dry Run Analysis (rector-symfony.php)"
    - "Rector Dry Run Analysis (rector-quality.php)"

enforce_admins: true
required_pull_request_reviews:
  required_approving_review_count: 1
  dismiss_stale_reviews: true
  require_code_owner_reviews: true
restrictions: null
```

#### Development Branch Protection
```yaml
# Development Branch Protection (less strict)
required_status_checks:
  strict: false
  contexts:
    - "Code Quality Checks"
    - "Rector Configuration Validation"

enforce_admins: false
required_pull_request_reviews:
  required_approving_review_count: 1
  dismiss_stale_reviews: false
restrictions: null
```

### Setup Instructions

1. Go to repository Settings → Branches
2. Click "Add rule" for each protected branch
3. Configure the settings as shown above
4. Save the protection rules

## Performance Benchmarking

### PHPBench Configuration

The project includes PHPBench for performance monitoring:

```bash
# Run benchmarks locally
vendor/bin/phpbench run --report=table

# Run with CI profile (faster)
vendor/bin/phpbench run --report=table --profile=ci

# Generate HTML report
vendor/bin/phpbench run --report=html --output=build/
```

### Benchmark Suites

- **HashIdPerformanceBench**: Core Hashids library performance
- Future: Router decoration performance
- Future: Parameter processing performance

### CI Integration

Performance benchmarks run automatically on pull requests to detect performance regressions.

## Secrets Configuration

### Required Secrets

| Secret | Purpose | Required |
|--------|---------|----------|
| `CODECOV_TOKEN` | Code coverage upload | No (public repos) |
| `STRYKER_DASHBOARD_API_KEY` | Mutation testing dashboard | Optional |

### Setup Instructions

1. Go to repository Settings → Secrets and variables → Actions
2. Add each required secret
3. Ensure secrets are available to the appropriate environments

## Troubleshooting

### Common Issues

#### Matrix Job Failures
- Check for PHP/Symfony compatibility issues
- Review composer.json constraints
- Verify all dependencies support the version matrix

#### Quality Check Failures
- Run tools locally first: `vendor/bin/phpstan`, `vendor/bin/php-cs-fixer`
- Check for new deprecations in Symfony versions
- Review PHPStan configuration for new rules

#### Rector Validation Failures
- Ensure all Rector configurations are syntactically valid
- Check for missing dependencies in Rector rules
- Verify file paths in Rector configurations

### Local Testing

```bash
# Test the full quality suite locally
composer install
vendor/bin/phpunit --configuration=phpunit.xml.dist
vendor/bin/phpstan analyse --configuration=phpstan.neon.dist
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff
vendor/bin/rector --dry-run --config=rector.php

# Test individual Rector configurations
vendor/bin/rector --dry-run --config=rector-php81.php
vendor/bin/rector --dry-run --config=rector-symfony.php
vendor/bin/rector --dry-run --config=rector-quality.php
```

## Monitoring

### Key Metrics

- **Test Coverage**: Target 90%+
- **Mutation Score**: Target 80% MSI, 90% Covered MSI
- **PHPStan Level**: Level 9 (maximum)
- **Build Time**: Monitor for performance regressions
- **Success Rate**: Track workflow success rates

### Notifications

Configure GitHub notifications for:
- Failed workflows
- Security vulnerabilities
- Coverage drops
- Performance regressions

## Future Enhancements

1. **Parallel Testing**: Implement parallel test execution
2. **Cache Optimization**: Improve dependency caching
3. **Performance Monitoring**: Add detailed performance tracking
4. **Release Automation**: Add automated release workflows
5. **Deployment**: Add staging/production deployment workflows