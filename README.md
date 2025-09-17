# HashId Bundle - Modernized Fork

> **A modernized fork of [PGSSoft/HashId](https://github.com/PGSSoft/HashId) bundle, updated for PHP 8.3 and Symfony 6.4/7.0**

![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)
![Symfony 6.4/7.0](https://img.shields.io/badge/Symfony-6.4%20%7C%207.0-green.svg)
[![Latest Stable Version](https://poser.pugx.org/uniacid/hashid-bundle/v/stable)](https://packagist.org/packages/uniacid/hashid-bundle)
[![License](https://poser.pugx.org/uniacid/hashid-bundle/license)](https://packagist.org/packages/uniacid/hashid-bundle)

Symfony bundle for automatically encoding integer route parameters and decoding request parameters using [Hashids](http://www.hashids.org/)

## 🚀 Version 4.0 Features

- **PHP 8.1+ Attributes**: Native `#[Hash]` attributes replace annotations
- **Modern PHP Support**: PHP 8.1, 8.2, and 8.3 features including readonly properties, typed constants
- **Symfony 6.4 LTS & 7.0**: Full compatibility with latest Symfony versions
- **75.3% Rector Automation**: Automated migration from v3.x using Rector
- **PHPStan Level 9**: Enhanced type safety and code quality
- **Multiple Hasher Support**: Configure different hashers with unique settings

## Why Use HashId?

Transform predictable integer URL parameters into obfuscated strings automatically:
- `/order/315` → `/order/4w9aA11avM`
- `/user/1337` → `/user/X46dBNxd79`
- `/hash-id/demo/decode/216/30` → `/hash-id/demo/decode/X46dBNxd79/ePOwvANg`

### Benefits

- **Security**: Prevent resource enumeration attacks
- **Transparency**: No code changes needed - works with existing `generateUrl()` and `{{ url() }}`
- **Compatibility**: Full Doctrine ParamConverter support
- **Flexibility**: Configure salt, minimum length, and alphabet

## Installation

```bash
composer require uniacid/hashid-bundle:^4.0
```

### Requirements

- PHP 8.1 or higher (8.3 recommended)
- Symfony 6.4 LTS or 7.0
- hashids/hashids ^4.0 or ^5.0

## Configuration

```yaml
# config/packages/pgs_hash_id.yaml
pgs_hash_id:
    salt: '%env(HASHID_SALT)%'
    min_hash_length: 10
    alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'

    # v4.0 Compatibility Settings (optional)
    compatibility:
        suppress_deprecations: false  # Set to true during migration
        prefer_attributes: true        # Use attributes when both are present
```

## Usage

### Modern Usage with PHP 8.1+ Attributes (Recommended)

```php
<?php

namespace App\Controller;

use Pgs\HashIdBundle\Attribute\Hash;
use Symfony\Component\Routing\Attribute\Route;

class OrderController
{
    #[Route('/order/{id}')]
    #[Hash('id')]
    public function show(int $id): Response
    {
        // $id is automatically decoded from hash to integer
        // Example: URL /order/4w9aA11avM → $id = 315
    }

    #[Route('/compare/{id}/{otherId}')]
    #[Hash(['id', 'otherId'])]
    public function compare(int $id, int $otherId): Response
    {
        // Multiple parameters can be hashed
    }
}
```

### Legacy Annotation Support (Deprecated, will be removed in v5.0)

```php
use Pgs\HashIdBundle\Annotation\Hash;
use Symfony\Component\Routing\Annotation\Route;

class LegacyController
{
    /**
     * @Route("/user/{id}")
     * @Hash("id")
     */
    public function edit(int $id): Response
    {
        // Still works in v4.0 for backward compatibility
    }
}
```

### Twig Templates

No changes needed! The bundle works transparently:

```twig
{# Automatically encodes the id parameter #}
<a href="{{ url('order_show', {'id': order.id}) }}">View Order</a>
{# Generates: /order/4w9aA11avM #}
```

### Controllers and Services

```php
// Automatic encoding in controllers
return $this->redirectToRoute('order_show', ['id' => $orderId]);

// Automatic encoding in services
$url = $this->router->generate('order_show', ['id' => $orderId]);
```

## Migration from v3.x

### Automated Migration with Rector (75.3% Automation)

```bash
# Install Rector if not present
composer require rector/rector --dev

# Run automated migration (dry-run first)
vendor/bin/rector process --config=rector.php --dry-run

# Apply changes
vendor/bin/rector process --config=rector.php
```

### Key Changes in v4.0

- **Minimum PHP**: 7.2 → 8.1
- **Symfony**: 4.4/5.x → 6.4/7.0
- **Annotations**: Deprecated in favor of attributes
- **PHPStan**: Level 4 → Level 9
- **Type Coverage**: 65% → 95%

For detailed migration instructions, see [UPGRADE-4.0.md](UPGRADE-4.0.md).

## Advanced Features

### Multiple Hasher Configuration

```yaml
# config/packages/pgs_hash_id.yaml
pgs_hash_id:
    # Default hasher configuration
    salt: '%env(HASHID_SALT)%'
    min_hash_length: 10

    hashers:
        secure:
            salt: '%env(SECURE_HASHID_SALT)%'
            min_hash_length: 20
        public:
            salt: 'public-content'
            min_hash_length: 8
```

### Doctrine ParamConverter Integration

Works seamlessly with Doctrine entities:

```php
#[Route('/order/{id}/invoice')]
#[Hash('id')]
public function invoice(#[MapEntity] Order $order): Response
{
    // Doctrine automatically loads the Order entity
    // using the decoded integer ID
}
```

## Testing

Run the test suite:

```bash
# Run all tests
vendor/bin/phpunit

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/

# Run documentation validation tests
vendor/bin/phpunit tests/Documentation/
```

## Documentation

- [Installation Guide](docs/installation.md)
- [Configuration Reference](docs/configuration-reference.md)
- [Migration Guide](UPGRADE-4.0.md)
- [Rector Metrics](docs/RECTOR-METRICS.md)
- [API Documentation](docs/api/README.md)
- [Security Considerations](docs/SECURITY.md)
- [Performance Guide](docs/performance.md)

## Contributing

Bug reports and pull requests are welcome on GitHub at [https://github.com/uniacid/HashId](https://github.com/uniacid/HashId).

See [CONTRIBUTING.md](CONTRIBUTING.md) for development setup and guidelines.

## About

### Original Work
This bundle is a fork of the excellent [HashId Bundle](https://github.com/PGSSoft/HashId) originally created by [PGS Software](https://www.pgs-soft.com/).

### This Fork
This modernized fork is maintained by [uniacid](https://github.com/uniacid) and focuses on:
- PHP 8.3 compatibility and modern features
- Symfony 6.4 LTS and 7.0 support
- Automated migration tools with Rector
- Enhanced performance and type safety

### Version 4.0 Modernization

- Modernization Lead: AI-Assisted Development Team
- Rector Automation: 75.3% automation rate achieved
- Testing: PHPUnit 10 migration with 90%+ coverage
- Documentation: Comprehensive upgrade guides and API documentation

## License

This bundle is released under the MIT license. See the [LICENSE](LICENSE) file for details.

## Follow us

[![Twitter URL](https://img.shields.io/twitter/url/http/shields.io.svg?style=social)](https://twitter.com/intent/tweet?text=https://github.com/PGSSoft/HashId)
[![Twitter Follow](https://img.shields.io/twitter/follow/pgssoftware.svg?style=social&label=Follow)](https://twitter.com/pgssoftware)