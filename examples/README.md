# HashId Bundle Examples

This directory contains example applications demonstrating the HashId Bundle features for both Symfony 6.4 and 7.0.

## Overview

The HashId Bundle automatically encodes/decodes integer route parameters using the Hashids library. These examples show how it transforms predictable URLs like `/order/315` into obfuscated URLs like `/order/4w9aA11avM` while maintaining transparent usage in controllers and Twig templates.

## Examples Included

### 1. Symfony 6.4 Example (`symfony-6.4/`)
- Demonstrates both annotation and attribute support
- Shows backward compatibility with v3.x patterns
- Includes full CRUD operations with HashId encoding

### 2. Symfony 7.0 Example (`symfony-7.0/`)
- Uses modern PHP 8 attributes exclusively
- Demonstrates latest Symfony features
- Shows migration path from annotations to attributes

### 3. Migration Example (`migration-example/`)
- Before and after code samples
- Rector configuration for automated migration
- Step-by-step migration guide

## Quick Start

### Using Docker (Recommended)

1. **Build and start containers:**
   ```bash
   cd examples
   docker-compose up -d
   ```

2. **Access the applications:**
   - Symfony 6.4: http://localhost:8064
   - Symfony 7.0: http://localhost:8070

3. **Stop containers:**
   ```bash
   docker-compose down
   ```

### Manual Setup

#### Symfony 6.4 Example

```bash
cd examples/symfony-6.4
composer install
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php -S localhost:8064 -t public
```

#### Symfony 7.0 Example

```bash
cd examples/symfony-7.0
composer install
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php -S localhost:8070 -t public
```

## Features Demonstrated

### 1. Automatic URL Encoding
- Integer IDs in URLs are automatically encoded
- Example: `/order/1` becomes `/order/jR` (actual hash depends on salt configuration)

### 2. Transparent Controller Usage
```php
// The controller receives the decoded integer
#[Route('/{id}', name: 'order_show')]
#[Hash('id')]
public function show(int $id): Response
{
    // $id is the decoded integer value
    $order = $this->orderRepository->find($id);
}
```

### 3. Twig Integration
```twig
{# Use raw IDs in templates - they're automatically encoded #}
<a href="{{ url('order_show', {'id': order.id}) }}">View Order</a>
```

### 4. Configuration
Each example includes configuration in `config/packages/pgs_hash_id.yaml`:
```yaml
pgs_hash_id:
    salt: 'your-unique-salt-here'
    min_hash_length: 8
    alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
```

## Testing the Examples

### Manual Testing

1. **Create a new order:**
   - Navigate to `/order/new`
   - Fill in the form and submit
   - Note the encoded ID in the redirect URL

2. **View orders:**
   - Go to `/order/` to see the list
   - Click on any order to view details
   - Observe the encoded ID in the URL

3. **Edit an order:**
   - Click "Edit" on any order
   - Note that the edit URL also has an encoded ID

### Automated Testing

Run the test suite from the main bundle directory:

```bash
cd ../
vendor/bin/phpunit examples/tests/
```

## Key Differences Between Examples

### Symfony 6.4 (Transitional)
- Supports both `@Hash` annotations and `#[Hash]` attributes
- Compatible with PHP 8.1+
- Uses Symfony 6.4 LTS for stability

### Symfony 7.0 (Modern)
- Uses `#[Hash]` attributes exclusively
- Requires PHP 8.2+
- Leverages latest Symfony 7.0 features

## Common Issues and Solutions

### Issue: "Order not found" error
**Solution:** The database might be empty. Create sample data:
```bash
php bin/console doctrine:query:sql "INSERT INTO \`order\` (order_number, customer_name, total_amount, status, created_at) VALUES ('ORD-001', 'John Doe', '99.99', 'pending', datetime('now'))"
```

### Issue: URLs not being encoded
**Solution:** Check that:
1. The HashId bundle is registered in `config/bundles.php`
2. The `@Hash` annotation or `#[Hash]` attribute is present on controller methods
3. The configuration file exists at `config/packages/pgs_hash_id.yaml`

### Issue: Docker containers won't start
**Solution:** Ensure ports 8064 and 8070 are not in use:
```bash
lsof -i :8064
lsof -i :8070
```

## Migration from v3.x to v4.x

See the `migration-example/` directory for:
- Side-by-side comparison of v3 and v4 code
- Rector configuration for automated migration
- Manual migration checklist
- Common migration patterns

## Additional Resources

- [Main Bundle Documentation](../README.md)
- [Upgrade Guide](../UPGRADE-4.0.md)
- [API Documentation](../docs/api/)
- [Contributing Guide](../CONTRIBUTING.md)

## License

These examples are part of the HashId Bundle and are released under the MIT License.