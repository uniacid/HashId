# Symfony 7.0 HashId Bundle Example

This example demonstrates the HashId Bundle integration with Symfony 7.0, using modern PHP 8 attributes exclusively for a clean, forward-looking implementation.

## Requirements

- PHP 8.2 or higher
- Composer
- SQLite (included with PHP)

## Installation

### Option 1: Using Docker (Recommended)

From the parent `examples/` directory:

```bash
docker-compose up symfony70
```

The application will be available at http://localhost:8070

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
   ('ORD-001', 'Alice Cooper', '299.99', 'pending', datetime('now')),
   ('ORD-002', 'Bob Dylan', '449.50', 'processing', datetime('now')),
   ('ORD-003', 'Charlie Parker', '175.25', 'shipped', datetime('now'))"
   ```

4. **Start the development server:**
   ```bash
   php -S localhost:8070 -t public
   ```

5. **Access the application:**
   Open http://localhost:8070 in your browser

## Features Demonstrated

### 1. Modern Attribute Usage

This example uses PHP 8 attributes exclusively:

```php
use Pgs\HashIdBundle\Attribute\Hash;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{id}', name: 'order_show')]
#[Hash('id')]
public function show(int $id): Response
{
    // $id is automatically decoded from the hash
}
```

### 2. Multiple Parameter Encoding

```php
#[Route('/order/{orderId}/item/{itemId}')]
#[Hash(['orderId', 'itemId'])]
public function orderItem(int $orderId, int $itemId): Response
{
    // Both parameters are decoded
}
```

### 3. Advanced Features

```php
// Custom parameter names
#[Route('/user/{userId}/order/{orderId}')]
#[Hash('userId')]
#[Hash('orderId')]
public function userOrder(int $userId, int $orderId): Response
{
    // Individual hash attributes for fine control
}
```

### 4. Service Integration

The bundle integrates seamlessly with Symfony 7's dependency injection:

```php
public function __construct(
    private readonly OrderRepository $orderRepository,
    private readonly EntityManagerInterface $entityManager
) {
    // Using constructor property promotion
}
```

## Configuration

The bundle is configured in `config/packages/pgs_hash_id.yaml`:

```yaml
pgs_hash_id:
    salt: 'symfony70-example-salt-2024'
    min_hash_length: 8
    alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
```

### Environment-Specific Configuration

You can override settings per environment:

```yaml
# config/packages/dev/pgs_hash_id.yaml
pgs_hash_id:
    min_hash_length: 4  # Shorter hashes in development
```

## Project Structure

```
symfony-7.0/
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
│   │   └── OrderController.php  # Controller with Hash attributes
│   ├── Entity/
│   │   └── Order.php     # Order entity with typed properties
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

## Modern PHP 8 Features Used

### 1. Constructor Property Promotion

```php
public function __construct(
    private readonly OrderRepository $orderRepository,
    private readonly EntityManagerInterface $entityManager
) {
}
```

### 2. Typed Properties

```php
class Order
{
    private ?int $id = null;
    private ?string $orderNumber = null;
    private ?string $customerName = null;
}
```

### 3. Match Expressions (if applicable)

```php
$statusLabel = match($order->getStatus()) {
    'pending' => 'Awaiting Processing',
    'shipped' => 'On the Way',
    'delivered' => 'Completed',
    default => 'Unknown'
};
```

### 4. Null-safe Operator

```php
$customerEmail = $order->getCustomer()?->getEmail() ?? 'No email';
```

## Usage Examples

### API-Style Routes

Create RESTful routes with encoded IDs:

```php
#[Route('/api/orders/{id}', methods: ['GET'])]
#[Hash('id')]
public function apiShow(int $id): JsonResponse
{
    $order = $this->orderRepository->find($id);
    return $this->json($order);
}
```

### Form Handling

Forms work transparently with the HashId bundle:

```php
#[Route('/{id}/edit', methods: ['GET', 'POST'])]
#[Hash('id')]
public function edit(Request $request, int $id): Response
{
    $order = $this->orderRepository->find($id);
    // Form handling with decoded ID
}
```

## Testing

Run tests from the main bundle directory:

```bash
cd ../../
vendor/bin/phpunit examples/tests/ExampleApplicationTest.php --filter=Symfony70
```

## Performance Considerations

Symfony 7.0 with PHP 8.2+ offers improved performance:

- **Preloading**: Configure opcache.preload for production
- **JIT Compilation**: Enable JIT for mathematical operations in hash generation
- **Lazy Loading**: Entities use lazy ghost objects for better performance

## Troubleshooting

### PHP Version Issues

Ensure PHP 8.2+ is installed:

```bash
php -v
```

### Attribute Not Recognized

Clear the cache and rebuild:

```bash
php bin/console cache:clear
php bin/console cache:warmup
```

### Database Connection Issues

Check the DATABASE_URL in `.env`:

```env
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

## Deployment Considerations

For production deployment:

1. **Optimize Composer:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. **Compile container:**
   ```bash
   php bin/console cache:warmup --env=prod
   ```

3. **Use environment variables:**
   ```env
   APP_ENV=prod
   APP_DEBUG=0
   ```

## Differences from Symfony 6.4 Example

| Feature | Symfony 6.4 | Symfony 7.0 |
|---------|-------------|-------------|
| PHP Version | 8.1+ | 8.2+ |
| Annotations | Supported | Not used |
| Attributes | Supported | Exclusive |
| Property Types | Mixed | Fully typed |
| Constructor | Traditional | Property promotion |

## Next Steps

- Review the [migration example](../migration-example/) for upgrade patterns
- Explore [advanced HashId features](../../docs/advanced.md)
- Check the [API documentation](../../docs/api/)
- Read about [security best practices](../../docs/security.md)

## License

This example is part of the HashId Bundle and is released under the MIT License.