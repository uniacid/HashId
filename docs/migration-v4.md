# Migration Guide: HashId Bundle v3 to v4

This guide helps you migrate from HashId Bundle v3.x to v4.x, which introduces modern PHP 8.3 features, environment variable support, and multiple hasher configurations.

## Table of Contents
- [Overview](#overview)
- [Breaking Changes](#breaking-changes)
- [Migration Steps](#migration-steps)
- [Configuration Changes](#configuration-changes)
- [Controller Updates](#controller-updates)
- [Environment Variables](#environment-variables)
- [Multiple Hashers](#multiple-hashers)
- [Backward Compatibility](#backward-compatibility)

## Overview

HashId Bundle v4 modernizes the codebase with:
- PHP 8.3 features (readonly properties, typed constants)
- Native PHP attributes instead of annotations
- Environment variable support for sensitive configuration
- Multiple hasher support for different security contexts
- Typed configuration with validation
- Enhanced error handling with enums

## Breaking Changes

### Minimum Requirements
- PHP 8.1+ (was PHP 7.4+)
- Symfony 6.4+ recommended (5.4+ supported)
- Doctrine Annotations are deprecated (use PHP attributes)

### Configuration Structure
While v3 configuration still works, the new structure is recommended:

```yaml
# Before (v3.x)
pgs_hash_id:
    converter:
        hashids:
            salt: 'hardcoded-salt'
            min_hash_length: 10

# After (v4.x)
pgs_hash_id:
    hashers:
        default:
            salt: '%env(HASHID_SALT)%'
            min_hash_length: 10
```

## Migration Steps

### Step 1: Update Dependencies

```bash
composer require pgs/hashid-bundle:^4.0
```

### Step 2: Update Configuration

1. Keep your existing v3 configuration initially (it still works)
2. Add environment variables to `.env.local`:

```bash
# .env.local
HASHID_SALT=your-existing-salt-value
```

3. Update your configuration to use environment variables:

```yaml
# config/packages/pgs_hash_id.yaml
pgs_hash_id:
    hashers:
        default:
            salt: '%env(HASHID_SALT)%'
            min_hash_length: 10
            alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
```

### Step 3: Update Controllers

Replace annotations with PHP attributes:

```php
// Before (v3.x)
use Pgs\HashIdBundle\Annotation\Hash;

/**
 * @Route("/order/{id}")
 * @Hash("id")
 */
public function show(int $id) { }

// After (v4.x)
use Pgs\HashIdBundle\Attribute\Hash;

#[Route('/order/{id}')]
#[Hash('id')]
public function show(int $id) { }
```

### Step 4: Test Your Application

1. Clear the cache:
```bash
php bin/console cache:clear
```

2. Test URL generation and parameter decoding
3. Verify existing URLs still work

## Configuration Changes

### Basic Configuration

```yaml
# config/packages/pgs_hash_id.yaml
pgs_hash_id:
    hashers:
        default:
            salt: '%env(HASHID_SALT)%'
            min_hash_length: 10
            alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
            enabled: true  # Optional, defaults to true
```

### Typed Environment Variables

```yaml
pgs_hash_id:
    hashers:
        default:
            salt: '%env(string:HASHID_SALT)%'
            min_hash_length: '%env(int:HASHID_MIN_LENGTH)%'
            alphabet: '%env(string:HASHID_ALPHABET)%'
```

### Complex Environment Variables

```yaml
pgs_hash_id:
    hashers:
        default:
            # Use default value if env var not set
            salt: '%env(default:default_salt:HASHID_SALT)%'
            # Read from file
            alphabet: '%env(file:HASHID_ALPHABET_FILE)%'
            # Combine with processors
            min_hash_length: '%env(int:default:10:HASHID_LENGTH)%'
```

## Controller Updates

### Single Parameter

```php
use Pgs\HashIdBundle\Attribute\Hash;

#[Route('/user/{id}')]
#[Hash('id')]
public function show(int $id): Response
{
    // $id is automatically decoded
}
```

### Multiple Parameters

```php
#[Route('/compare/{id1}/{id2}')]
#[Hash(['id1', 'id2'])]
public function compare(int $id1, int $id2): Response
{
    // Both parameters are decoded
}
```

### With Specific Hasher

```php
#[Route('/secure/{userId}')]
#[Hash('userId', hasher: 'secure')]
public function secureAction(int $userId): Response
{
    // Uses the 'secure' hasher configuration
}
```

## Environment Variables

### Basic Setup

Create `.env.local` with your configuration:

```bash
# Required
HASHID_SALT=your-unique-salt-here

# Optional (with defaults)
HASHID_MIN_LENGTH=10
HASHID_ALPHABET=abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890
```

### Multiple Environments

```bash
# .env.dev
HASHID_SALT=dev-salt-not-secret

# .env.prod (or use secret management)
HASHID_SALT=production-secret-salt
```

## Multiple Hashers

### Configuration

```yaml
pgs_hash_id:
    hashers:
        # Default hasher for general use
        default:
            salt: '%env(HASHID_SALT)%'
            min_hash_length: 10
            
        # Secure hasher for sensitive data
        secure:
            salt: '%env(HASHID_SECURE_SALT)%'
            min_hash_length: 20
            alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%'
            
        # Public hasher for public content
        public:
            salt: '%env(HASHID_PUBLIC_SALT)%'
            min_hash_length: 5
            alphabet: 'abcdefghijklmnopqrstuvwxyz1234567890'
```

### Usage in Controllers

```php
// Default hasher
#[Hash('id')]

// Specific hasher
#[Hash('userId', hasher: 'secure')]
#[Hash('postId', hasher: 'public')]

// Multiple parameters with different hashers
#[Hash('orderId')]  // uses 'default'
#[Hash('userId', hasher: 'secure')]
```

### Twig Usage

```twig
{# Default hasher #}
{{ url('route_name', {id: entity.id}) }}

{# The hasher is determined by the controller attribute #}
{{ url('secure_route', {userId: user.id}) }}
```

## Backward Compatibility

### Keeping v3 Configuration

The v3 configuration format is still supported:

```yaml
pgs_hash_id:
    converter:
        hashids:
            salt: 'your-salt'
            min_hash_length: 10
            alphabet: 'abc123'
```

This is automatically mapped to the 'default' hasher internally.

### Gradual Migration

1. **Phase 1**: Update to v4 but keep v3 configuration
2. **Phase 2**: Move salts to environment variables
3. **Phase 3**: Migrate to new 'hashers' configuration
4. **Phase 4**: Add multiple hashers if needed
5. **Phase 5**: Update controllers to use attributes

### URL Compatibility

Existing URLs with hashes generated by v3 will continue to work in v4 as long as you use the same salt and configuration.

## Troubleshooting

### Common Issues

1. **"Hasher not found" error**
   - Ensure the hasher is configured in `pgs_hash_id.hashers`
   - Check spelling of hasher name in controller attribute

2. **Different hashes after migration**
   - Verify salt value matches exactly
   - Check alphabet and min_hash_length settings

3. **Environment variables not resolved**
   - Clear cache: `php bin/console cache:clear`
   - Verify `.env.local` is loaded
   - Check for typos in variable names

### Debug Commands

```bash
# Check configuration
php bin/console debug:config pgs_hash_id

# Check environment variables
php bin/console debug:container --env-vars

# List parameters
php bin/console debug:container --parameters | grep pgs_hash_id
```

## Further Resources

- [Configuration Examples](../examples/configuration/)
- [Multiple Hashers Guide](./multiple-hashers.md)
- [Environment Variables Best Practices](./environment-variables.md)
- [API Documentation](./api.md)