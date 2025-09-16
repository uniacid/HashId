# Symfony 6.4 HashId Bundle Example

This example demonstrates the HashId Bundle integration with Symfony 6.4 LTS, showing both annotation and attribute support for maximum compatibility.

## Requirements

- PHP 8.1 or higher
- Composer
- SQLite (included with PHP)

## Installation

### Option 1: Using Docker (Recommended)

From the parent `examples/` directory:

```bash
docker-compose up symfony64
```

The application will be available at http://localhost:8064

### Option 2: Manual Installation

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Create the database:**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:schema:create
   ```

3. **Load sample data (optional):**
   ```bash
   php bin/console doctrine:query:sql "INSERT INTO \`order\` (order_number, customer_name, total_amount, status, created_at) VALUES
   ('ORD-001', 'John Doe', '99.99', 'pending', datetime('now')),
   ('ORD-002', 'Jane Smith', '149.50', 'shipped', datetime('now')),
   ('ORD-003', 'Bob Johnson', '75.25', 'delivered', datetime('now'))"
   ```

4. **Start the development server:**
   ```bash
   php -S localhost:8064 -t public
   ```

5. **Access the application:**
   Open http://localhost:8064 in your browser

## Features Demonstrated

### 1. Annotation Support (v3.x Compatible)

```php
use Pgs\HashIdBundle\Annotation\Hash;

/**
 * @Hash("id")
 */
#[Route('/{id}', name: 'order_show')]
public function show(int $id): Response
{
    // $id is automatically decoded
}
```

### 2. Attribute Support (v4.x Style)

You can also use modern PHP attributes:

```php
use Pgs\HashIdBundle\Attribute\Hash;

#[Route('/{id}', name: 'order_show')]
#[Hash('id')]
public function show(int $id): Response
{
    // $id is automatically decoded
}
```

### 3. Multiple Parameters

```php
/**
 * @Hash({"orderId", "customerId"})
 */
#[Route('/order/{orderId}/customer/{customerId}')]
public function orderCustomer(int $orderId, int $customerId): Response
{
    // Both parameters are decoded
}
```

### 4. Twig Integration

Templates work transparently with raw IDs:

```twig
{# The ID is automatically encoded in the URL #}
<a href="{{ path('order_show', {'id': order.id}) }}">
    View Order #{{ order.orderNumber }}
</a>

{# Generates: /order/4w9aA11avM instead of /order/1 #}
```

## Configuration

The bundle is configured in `config/packages/pgs_hash_id.yaml`:

```yaml
pgs_hash_id:
    salt: 'symfony64-example-salt-2024'
    min_hash_length: 8
    alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
```

### Configuration Options

- **salt**: Unique string to make your hashes unique
- **min_hash_length**: Minimum length of generated hashes
- **alphabet**: Characters to use in hash generation

## Project Structure

```
symfony-6.4/
├── bin/
│   └── console           # Symfony console command
├── config/
│   ├── bundles.php       # Bundle registration
│   ├── packages/         # Package configuration
│   │   ├── doctrine.yaml
│   │   ├── framework.yaml
│   │   ├── pgs_hash_id.yaml  # HashId configuration
│   │   └── twig.yaml
│   ├── routes.yaml       # Routing configuration
│   └── services.yaml     # Service configuration
├── public/
│   └── index.php         # Application entry point
├── src/
│   ├── Controller/
│   │   └── OrderController.php  # Example controller with HashId
│   ├── Entity/
│   │   └── Order.php     # Order entity
│   ├── Repository/
│   │   └── OrderRepository.php
│   └── Kernel.php
├── templates/
│   ├── base.html.twig
│   └── order/            # Order templates
│       ├── index.html.twig
│       ├── show.html.twig
│       ├── edit.html.twig
│       └── new.html.twig
├── var/                  # Cache and logs
├── .env                  # Environment configuration
├── composer.json
└── README.md            # This file
```

## Usage Examples

### Creating an Order

1. Navigate to http://localhost:8064/order/new
2. Fill in the customer name and total amount
3. Submit the form
4. You'll be redirected to the order detail page with an encoded URL

### Viewing Orders

1. Go to http://localhost:8064/order/
2. Click on any order to view details
3. Notice the URL contains an encoded ID like `/order/jR` instead of `/order/1`

### URL Examples

- List all orders: `/order/`
- View order: `/order/4w9aA11avM` (encoded from ID 1)
- Edit order: `/order/4w9aA11avM/edit`
- Delete order: `/order/4w9aA11avM/delete` (POST only)

## Testing

Run tests from the main bundle directory:

```bash
cd ../../
vendor/bin/phpunit examples/tests/ExampleApplicationTest.php --filter=Symfony64
```

## Troubleshooting

### Database Issues

If you get database errors, recreate it:

```bash
rm var/data.db
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
```

### Cache Issues

Clear the cache:

```bash
php bin/console cache:clear
```

### Port Already in Use

Change the port:

```bash
php -S localhost:8065 -t public
```

## Migration Notes

This example maintains compatibility with HashId Bundle v3.x while demonstrating v4.x features:

- **Annotations** (`@Hash`) work for backward compatibility
- **Attributes** (`#[Hash]`) are available for modern code
- Both can be used simultaneously during migration

## Next Steps

- Review the [Symfony 7.0 example](../symfony-7.0/) for modern attribute-only usage
- Check the [migration example](../migration-example/) for upgrade patterns
- Read the [main documentation](../../README.md) for advanced features

## License

This example is part of the HashId Bundle and is released under the MIT License.