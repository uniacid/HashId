# Installation Guide

This guide will walk you through installing and configuring the HashId Bundle v4.0 in your Symfony application.

## Prerequisites

Before installing HashId Bundle, ensure your project meets these requirements:

- **PHP**: 8.1 or higher (8.3 recommended)
- **Symfony**: 6.4 LTS or 7.0
- **Composer**: Latest version recommended

## Installation Steps

### Step 1: Install via Composer

```bash
composer require uniacid/hashid-bundle:^4.0
```

> **Note**: This is a modernized fork of the original `pgs-soft/hashid-bundle`. If you're migrating from the original bundle, see the [Migration section](#migrating-from-the-original-bundle) below.

### Step 2: Bundle Registration

With Symfony Flex, the bundle should be automatically registered. If not using Flex or if registration fails, manually register the bundle:

```php
// config/bundles.php
return [
    // ... other bundles
    Pgs\HashIdBundle\PgsHashIdBundle::class => ['all' => true],
];
```

### Step 3: Create Configuration File

Create the bundle configuration file:

```yaml
# config/packages/pgs_hash_id.yaml
pgs_hash_id:
    salt: '%env(HASHID_SALT)%'
    min_hash_length: 10
    alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
```

### Step 4: Set Environment Variables

Add your hash salt to your environment configuration:

```bash
# .env.local
HASHID_SALT=your-secret-salt-value-here
```

> **Important**:
> - Use a unique salt for each environment (dev, staging, production)
> - Keep your salt secret - don't commit it to version control
> - Changing the salt will invalidate all existing hashed URLs

## Basic Configuration

### Minimal Configuration

The simplest configuration requires only a salt:

```yaml
# config/packages/pgs_hash_id.yaml
pgs_hash_id:
    salt: '%env(HASHID_SALT)%'
```

### Recommended Configuration

For production use, we recommend:

```yaml
# config/packages/pgs_hash_id.yaml
pgs_hash_id:
    salt: '%env(HASHID_SALT)%'
    min_hash_length: 10  # Minimum length of generated hashes
    alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'

    # v4.0 features
    compatibility:
        suppress_deprecations: false  # Set to true during migration
        prefer_attributes: true        # Use attributes when both are present
```

## Verification

### Step 1: Create a Test Controller

Create a simple controller to test the installation:

```php
<?php

namespace App\Controller;

use Pgs\HashIdBundle\Attribute\Hash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestHashIdController extends AbstractController
{
    #[Route('/test-hash/{id}', name: 'test_hashid')]
    #[Hash('id')]
    public function test(int $id): Response
    {
        return new Response("Decoded ID: {$id}");
    }
}
```

### Step 2: Generate a Test URL

In any controller or Twig template, generate a URL:

```php
// In a controller
$url = $this->generateUrl('test_hashid', ['id' => 123]);
// Result: /test-hash/X46dBNxd79 (hash will vary based on your salt)
```

```twig
{# In a Twig template #}
<a href="{{ url('test_hashid', {'id': 123}) }}">Test HashId</a>
```

### Step 3: Test the Route

1. Visit the generated URL in your browser
2. You should see "Decoded ID: 123"
3. The URL should show the hashed value, not the integer

## Migrating from the Original Bundle

If you're currently using `pgs-soft/hashid-bundle` v3.x:

### Step 1: Update Composer

Replace the package in your `composer.json`:

```bash
# Remove old package
composer remove pgs-soft/hashid-bundle

# Add new package
composer require uniacid/hashid-bundle:^4.0
```

### Step 2: Update Imports

If you're using annotations, update your imports:

```php
// Old (still works in v4.0)
use Pgs\HashIdBundle\Annotation\Hash;

// New (recommended)
use Pgs\HashIdBundle\Attribute\Hash;
```

### Step 3: Migrate to Attributes

Run Rector for automated migration:

```bash
# Install Rector if not present
composer require rector/rector --dev

# Run migration
vendor/bin/rector process --config=vendor/uniacid/hashid-bundle/rector.php
```

For detailed migration instructions, see [UPGRADE-4.0.md](../UPGRADE-4.0.md).

## Troubleshooting

### Bundle Not Auto-Registered

If Symfony Flex doesn't auto-register the bundle:

1. Clear the cache: `php bin/console cache:clear`
2. Manually register in `config/bundles.php` (see Step 2 above)

### Configuration Not Loaded

If your configuration isn't being recognized:

1. Ensure the file is named `pgs_hash_id.yaml` (not `hash_id.yaml`)
2. Check file location: `config/packages/pgs_hash_id.yaml`
3. Clear cache: `php bin/console cache:clear`

### Hashes Not Generated

If URLs show plain integers instead of hashes:

1. Verify the `#[Hash]` attribute is present on your controller method
2. Check that the parameter name matches: `#[Hash('id')]` for parameter `$id`
3. Ensure the bundle is properly registered
4. Check logs: `tail -f var/log/dev.log`

### Different Hashes in Different Environments

This is expected behavior if you're using different salts. To maintain consistent URLs across environments:

1. Use the same salt (not recommended for security)
2. Or implement URL migration strategies for production deployments

### Class Not Found Errors

If you get "Class 'Pgs\HashIdBundle\...' not found":

1. Run `composer dump-autoload`
2. Clear cache: `php bin/console cache:clear`
3. Verify installation: `composer show uniacid/hashid-bundle`

## Advanced Configuration

For advanced configuration options, including:
- Multiple hasher configurations
- Custom alphabets
- Doctrine integration
- Performance tuning

See the [Configuration Reference](configuration-reference.md).

## Next Steps

- [Configuration Reference](configuration-reference.md) - All configuration options
- [Basic Usage](../README.md#usage) - Using the bundle in your application
- [Migration Guide](../UPGRADE-4.0.md) - Migrating from v3.x
- [API Documentation](api/README.md) - Detailed API reference

## Support

- [Report Issues](https://github.com/uniacid/HashId/issues)
- [View on GitHub](https://github.com/uniacid/HashId)
- [Original Bundle](https://github.com/PGSSoft/HashId) (v3.x)

---

> **Remember**: The HashId Bundle works transparently with Symfony's routing system. Once installed and configured, all route generation will automatically encode integer parameters marked with the `#[Hash]` attribute.