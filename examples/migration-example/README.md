# HashId Bundle Migration Example (v3.x to v4.x)

This directory contains examples and tools for migrating from HashId Bundle v3.x to v4.x.

## Overview

The migration from v3.x to v4.x primarily involves:
1. Converting annotations to PHP 8 attributes
2. Adding type hints to method parameters
3. Updating use statements
4. Optionally adopting modern PHP 8 features

## Directory Structure

```
migration-example/
├── v3-example/           # Original v3.x code with annotations
│   └── OrderController.php
├── v4-example/           # Migrated v4.x code with attributes
│   └── OrderController.php
├── rector.php            # Rector configuration for automation
├── migrate.sh            # Migration script
└── README.md            # This file
```

## Quick Migration Guide

### Automated Migration with Rector

1. **Preview changes:**
   ```bash
   ./migrate.sh
   ```

2. **Apply changes when prompted**

3. **Review and test the migrated code**

### Manual Migration Steps

If you prefer to migrate manually or need to understand the changes:

#### 1. Update Annotations to Attributes

**Before (v3.x):**
```php
use Pgs\HashIdBundle\Annotation\Hash;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/order/{id}", name="order_show")
 * @Hash("id")
 */
public function show($id): Response
```

**After (v4.x):**
```php
use Pgs\HashIdBundle\Attribute\Hash;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order/{id}', name: 'order_show')]
#[Hash('id')]
public function show(int $id): Response
```

#### 2. Multiple Parameters

**Before (v3.x):**
```php
/**
 * @Hash({"orderId", "itemId"})
 */
public function orderItem($orderId, $itemId): Response
```

**After (v4.x):**
```php
#[Hash(['orderId', 'itemId'])]
public function orderItem(int $orderId, int $itemId): Response
```

#### 3. Class-Level Routes

**Before (v3.x):**
```php
/**
 * @Route("/order")
 */
class OrderController extends AbstractController
```

**After (v4.x):**
```php
#[Route('/order')]
class OrderController extends AbstractController
```

#### 4. Constructor Property Promotion (Optional)

**Before (v3.x):**
```php
private $orderRepository;
private $entityManager;

public function __construct(
    OrderRepository $orderRepository,
    EntityManagerInterface $entityManager
) {
    $this->orderRepository = $orderRepository;
    $this->entityManager = $entityManager;
}
```

**After (v4.x):**
```php
public function __construct(
    private readonly OrderRepository $orderRepository,
    private readonly EntityManagerInterface $entityManager
) {
}
```

## Rector Configuration

The `rector.php` file is configured to:
- Convert HashId annotations to attributes
- Convert Symfony route annotations to attributes
- Add type hints where possible
- Import proper use statements

### Running Rector Manually

```bash
# Dry run (preview changes)
../../vendor/bin/rector process v3-example --config=rector.php --dry-run

# Apply changes
../../vendor/bin/rector process v3-example --config=rector.php
```

## Migration Checklist

- [ ] Back up your code before migration
- [ ] Update `composer.json` to require HashId Bundle v4.x
- [ ] Run `composer update pgs-soft/hashid-bundle`
- [ ] Run Rector or manually update annotations to attributes
- [ ] Add type hints to controller method parameters
- [ ] Update use statements from `Annotation` to `Attribute`
- [ ] Test all routes with HashId parameters
- [ ] Verify URL generation in templates still works
- [ ] Check that ParamConverter integration works (if used)
- [ ] Run your test suite
- [ ] Test in staging environment

## Common Issues and Solutions

### Issue: "Class Hash not found"
**Solution:** Update the use statement:
```php
// Old
use Pgs\HashIdBundle\Annotation\Hash;

// New
use Pgs\HashIdBundle\Attribute\Hash;
```

### Issue: "Attribute Hash cannot be used multiple times"
**Solution:** Combine parameters into a single attribute:
```php
// Instead of multiple attributes
#[Hash('param1')]
#[Hash('param2')]

// Use array syntax
#[Hash(['param1', 'param2'])]
```

### Issue: Type errors after migration
**Solution:** Add proper type hints:
```php
// Add int type hint for hashed parameters
public function show(int $id): Response
```

## Compatibility Mode

During migration, v4.x supports both annotations and attributes:

```php
// Both work in v4.x (transitional)
/**
 * @Hash("id")
 */
#[Hash('id')]
public function show(int $id): Response
```

This allows gradual migration of large codebases.

## Testing the Migration

1. **Unit Tests:**
   ```bash
   vendor/bin/phpunit tests/Controller/
   ```

2. **Functional Tests:**
   - Test that URLs are still encoded
   - Verify redirects work correctly
   - Check form submissions with encoded IDs

3. **Manual Testing:**
   - Navigate through your application
   - Check that all links contain encoded IDs
   - Verify CRUD operations work

## Performance Considerations

The attribute system in PHP 8 offers better performance:
- Attributes are parsed at compile time
- No runtime annotation parsing overhead
- Better IDE support and static analysis

## Next Steps After Migration

1. **Remove annotation support** (optional, for v5.x preparation)
2. **Adopt more PHP 8 features:**
   - Named arguments
   - Union types
   - Match expressions
   - Readonly properties

3. **Update your CI/CD:**
   - Ensure PHP 8.1+ is used
   - Update static analysis tools
   - Configure Rector for ongoing modernization

## Resources

- [Full Upgrade Guide](../../UPGRADE-4.0.md)
- [Breaking Changes](../../BREAKING-CHANGES.md)
- [Migration FAQ](../../docs/migration-faq.md)
- [Rector Documentation](https://github.com/rectorphp/rector)

## Support

If you encounter issues during migration:
1. Check the [FAQ](../../docs/migration-faq.md)
2. Review [closed issues](https://github.com/pgs-soft/hashid-bundle/issues?q=is%3Aissue+is%3Aclosed)
3. Open a new issue with migration details

## License

This example is part of the HashId Bundle and is released under the MIT License.