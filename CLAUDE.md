# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

HashId Bundle is a Symfony bundle that automatically encodes/decodes integer route parameters using the Hashids library. It transforms predictable URLs like `/order/315` into obfuscated URLs like `/order/4w9aA11avM` while maintaining transparent usage in controllers and Twig templates.

**Modernization Project**: This is a fork of PGSSoft/HashId being modernized for PHP 8.3 and Symfony 6.4. The goal is to implement modern PHP features while providing a clear migration path from the original v3.x bundle.

## Repository Structure

This project uses a **dual-repository development workflow**:

- **Public Fork**: `origin` → https://github.com/uniacid/HashId
  - Used for stable releases and public visibility
  - Receives cherry-picked stable features from development
  
- **Private Development**: `private` → https://github.com/uniacid/HashId-Modernization-Private
  - Active development repository for all work-in-progress
  - All feature branches and experimentation happen here
  - Private until ready for public release

### Branch Strategy
- `master` - Production code mirroring original PGSSoft/HashId
- `dev/modernization-v4` - Main development branch for v4.0 modernization
- `feature/*` - Feature branches for specific modernization tasks

### Git Workflow
```bash
# Daily development (pushes to private by default)
git checkout dev/modernization-v4
git pull private dev/modernization-v4

# Create features
git checkout -b feature/php83-attributes
git push private feature/php83-attributes --set-upstream

# Release to public when stable
git push origin dev/modernization-v4
```

## Development Commands

Based on CI configuration and modernization targets:

### Current (v3.x) Commands
```bash
# Code Quality Checks
vendor/bin/phpstan analyse --level=4 src -c tests/phpstan.neon
vendor/bin/phpcs --report-full --standard=PSR2 src tests
vendor/bin/php-cs-fixer fix --config=tests/php-cs-fixer.config.php --dry-run --diff src tests
vendor/bin/phpcpd src tests

# Testing
phpdbg -qrr vendor/bin/phpunit -c tests/phpunit.xml           # Full test suite with coverage
vendor/bin/phpunit -c tests/phpunit.xml --filter=MethodName  # Single test method
```

### Target (v4.0) Commands
```bash
# Modernized tooling for PHP 8.3/Symfony 6.4
vendor/bin/phpstan analyse --level=9 src -c tests/phpstan.neon
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --diff --dry-run
vendor/bin/phpunit --configuration=phpunit.xml.dist
```

## Architecture Overview

### Core Components

1. **Bundle Integration**: `PgsHashIdBundle.php` registers compiler passes and services
2. **Annotation System**: `@Hash("param")` or `@Hash({"param1", "param2"})` annotations mark parameters for encoding/decoding
   - **Modernization Target**: Replace with PHP 8.1+ `#[Hash('param')]` attributes
3. **Router Decoration**: `RouterDecorator` intercepts `generateUrl()` calls to encode parameters
4. **Parameter Processing**: Event subscribers handle request/response parameter conversion
5. **Hashids Conversion**: `ParametersProcessor/Converter/HashidsConverter.php` handles the actual encoding/decoding

### Key Implementation Details

- **Transparent Integration**: No changes needed to existing `generateUrl()` or Twig `{{ url() }}` calls
- **Annotation-Driven**: Controllers use `@Hash` annotations to specify which parameters to process
- **Doctrine Compatible**: Works with Symfony's ParamConverter for automatic entity loading
- **Internationalized Routes**: Supports localized route parameters
- **Configuration**: Salt, minimum length, and alphabet configurable via `config/packages/pgs_hash_id.yaml`

### Processing Flow

1. **URL Generation**: Router decorator intercepts `generateUrl()`, encodes annotated parameters
2. **Request Processing**: Event subscriber decodes parameters from incoming requests
3. **Controller Execution**: Controller receives decoded integer values as normal
4. **Response**: URLs in responses are automatically encoded when generated

## Modernization Goals (v4.0)

### Target Tech Stack
- **PHP**: 8.3+ (minimum 8.1)
- **Symfony**: 6.4 LTS (primary), 7.0 (secondary)
- **Testing**: PHPUnit 10.x, PHPStan level 9
- **Standards**: PSR-12 with PHP CS Fixer 3.40+

### Key Modernization Features
- **PHP 8.3 Typed Constants**: Configuration interfaces with typed constants
- **Attributes**: Replace annotations with native PHP attributes
- **Constructor Property Promotion**: Modern service class patterns
- **Readonly Properties**: Immutable configuration objects
- **JSON Validation**: Use `json_validate()` for API endpoints
- **Multiple Hashers**: Support different encoding strategies

### Development Phases
See `.agent-os/product/roadmap.md` for detailed 6-week implementation plan:
1. **Setup & Foundation** (Week 1)
2. **Core Modernization** (Weeks 2-3)
3. **Feature Enhancement** (Week 4)
4. **Testing & Documentation** (Week 5)
5. **Release Preparation** (Week 6)

## Development Standards

This project follows the Agent OS PHP/Symfony standards:
- PHP 8.3+ with typed properties and strict types
- Symfony 6.4 LTS patterns and best practices
- PSR-12 coding standards enforced by PHP CS Fixer
- PHPStan level 9 static analysis (upgrading from level 4)
- 90% minimum test coverage requirement (upgrading from 80%)
- Modern PHP features (attributes, readonly, typed constants)

## Bundle-Specific Testing

The test suite includes:
- Unit tests for all parameter processors and converters
- Integration tests with mock Symfony kernel (`tests/App/`)
- Annotation processing tests (will be modernized to attribute tests)
- Router decoration tests
- Doctrine converter compatibility tests
- Deprecation tests for backward compatibility

Test configuration uses a separate kernel in `tests/App/` to simulate bundle integration without a full Symfony application.

## Agent OS Integration

This project uses Agent OS for AI-assisted development:
- **Product Documentation**: See `.agent-os/product/` for mission, roadmap, and tech stack
- **Standards**: See `.agent-os/standards/` for PHP/Symfony coding standards
- **Instructions**: See `.agent-os/instructions/` for development workflows

Use Agent OS commands to create specs and manage development tasks according to the modernization roadmap.

## Migration Strategy

### Version 4.0 (Transitional)
- Support both annotations and attributes with deprecation warnings
- Maintain API compatibility with v3.x where possible
- Provide migration tools and comprehensive upgrade documentation

### Version 5.0 (Clean)
- Remove annotation support completely
- PHP 8.2+ minimum requirement
- Full Symfony 7.0 support