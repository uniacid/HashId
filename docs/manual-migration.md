# Manual Migration Guide

## Overview

While Rector automates 70%+ of the migration from HashId Bundle v3.x to v4.x, some changes require manual intervention. This guide covers all manual migration steps needed to complete your upgrade.

## Required Manual Changes

### 1. Service Definitions and Dependency Injection

#### Custom Service Configurations

If you have custom services that depend on HashId services, update the service names:

**Before (v3.x):**
```yaml
# config/services.yaml
services:
    App\Service\MyCustomService:
        arguments:
            $processor: '@pgs_hash_id.parameters_processor'
            $converter: '@pgs_hash_id.hashids_converter'
```

**After (v4.x):**
```yaml
# config/services.yaml
services:
    App\Service\MyCustomService:
        arguments:
            $processor: '@Pgs\HashIdBundle\Service\ParametersProcessor'
            $converter: '@Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter'
```

#### Service Aliases

Update any service aliases:

**Before:**
```yaml
services:
    app.hash_processor:
        alias: pgs_hash_id.parameters_processor
```

**After:**
```yaml
services:
    app.hash_processor:
        alias: Pgs\HashIdBundle\Service\ParametersProcessor
```

### 2. Custom Annotation/Attribute Readers

If you've extended the bundle's annotation reading capabilities:

#### Custom Annotation Provider

**Before:**
```php
<?php
namespace App\Service;

use Doctrine\Common\Annotations\Reader;
use Pgs\HashIdBundle\Annotation\Hash;

class CustomAnnotationProvider
{
    private Reader $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function getHashAnnotation(\ReflectionMethod $method): ?Hash
    {
        $annotations = $this->reader->getMethodAnnotations($method);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof Hash) {
                return $annotation;
            }
        }

        return null;
    }
}
```

**After:**
```php
<?php
namespace App\Service;

use Pgs\HashIdBundle\Attribute\Hash as HashAttribute;
use Pgs\HashIdBundle\Annotation\Hash as HashAnnotation;

class CustomAnnotationProvider
{
    public function getHashAnnotation(\ReflectionMethod $method): ?object
    {
        // Check for attribute first (preferred)
        $attributes = $method->getAttributes(HashAttribute::class);
        if (!empty($attributes)) {
            return $attributes[0]->newInstance();
        }

        // Fall back to annotation for compatibility
        if (class_exists('Doctrine\Common\Annotations\AnnotationReader')) {
            $reader = new \Doctrine\Common\Annotations\AnnotationReader();
            $annotation = $reader->getMethodAnnotation($method, HashAnnotation::class);
            if ($annotation) {
                return $annotation;
            }
        }

        return null;
    }
}
```

### 3. Event Subscribers and Listeners

#### Request/Response Event Handling

If you have custom event subscribers that interact with HashId processing:

**Before:**
```php
<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class HashIdEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        // Old reflection logic
        if (is_array($controller)) {
            $reflection = new \ReflectionMethod($controller[0], $controller[1]);
        } else {
            $reflection = new \ReflectionFunction($controller);
        }

        // Process annotations
        // ...
    }
}
```

**After:**
```php
<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Pgs\HashIdBundle\Attribute\Hash;

class HashIdEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        // Updated reflection logic for PHP 8.1+
        $reflector = $this->getReflector($controller);

        if (!$reflector instanceof \ReflectionMethod && !$reflector instanceof \ReflectionFunction) {
            return;
        }

        // Process attributes
        $attributes = $reflector->getAttributes(Hash::class);
        foreach ($attributes as $attribute) {
            $hash = $attribute->newInstance();
            // Process hash parameters
        }
    }

    private function getReflector(mixed $controller): \ReflectionMethod|\ReflectionFunction|null
    {
        if (is_array($controller) && count($controller) === 2) {
            return new \ReflectionMethod($controller[0], $controller[1]);
        }

        if (is_object($controller) && !$controller instanceof \Closure) {
            return new \ReflectionMethod($controller, '__invoke');
        }

        if ($controller instanceof \Closure || is_string($controller)) {
            return new \ReflectionFunction($controller);
        }

        return null;
    }
}
```

### 4. Twig Extensions

If you have custom Twig extensions that interact with HashId:

**Before:**
```php
<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HashIdExtension extends AbstractExtension
{
    private $parametersProcessor;

    public function __construct($parametersProcessor)
    {
        $this->parametersProcessor = $parametersProcessor;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('encode_id', [$this, 'encodeId']),
        ];
    }

    public function encodeId($id): string
    {
        return $this->parametersProcessor->encode($id);
    }
}
```

**After:**
```php
<?php
namespace App\Twig;

use Pgs\HashIdBundle\Service\ParametersProcessor;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HashIdExtension extends AbstractExtension
{
    public function __construct(
        private readonly ParametersProcessor $parametersProcessor
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('encode_id', $this->encodeId(...)),
        ];
    }

    public function encodeId(int|string $id): string
    {
        return $this->parametersProcessor->encode($id);
    }
}
```

### 5. Configuration Files

#### Bundle Configuration

Update configuration structure if using advanced features:

**Before:**
```yaml
# config/packages/pgs_hash_id.yaml
pgs_hash_id:
    salt: '%env(HASHID_SALT)%'
    min_hash_length: 10
    alphabet: 'abcdefghijklmnopqrstuvwxyz'
```

**After:**
```yaml
# config/packages/pgs_hash_id.yaml
pgs_hash_id:
    salt: '%env(HASHID_SALT)%'
    min_hash_length: 10
    alphabet: 'abcdefghijklmnopqrstuvwxyz'

    # New v4.0 settings
    compatibility:
        suppress_deprecations: false
        prefer_attributes: true
        legacy_mode: false
```

### 6. Test Updates

#### Controller Tests

Update controller tests to use attributes:

**Before:**
```php
<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testShowAction(): void
    {
        $client = static::createClient();

        // Test with plain ID
        $client->request('GET', '/user/123');
        $this->assertResponseIsSuccessful();
    }
}
```

**After:**
```php
<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pgs\HashIdBundle\Service\ParametersProcessor;

class UserControllerTest extends WebTestCase
{
    public function testShowAction(): void
    {
        $client = static::createClient();

        // Get the parameters processor to encode IDs
        $processor = static::getContainer()->get(ParametersProcessor::class);
        $encodedId = $processor->encode(123);

        // Test with encoded ID
        $client->request('GET', "/user/{$encodedId}");
        $this->assertResponseIsSuccessful();
    }
}
```

### 7. API Documentation

Update API documentation annotations/attributes:

**Before:**
```php
/**
 * @Route("/api/user/{id}", methods={"GET"})
 * @Hash("id")
 *
 * @OA\Get(
 *     path="/api/user/{id}",
 *     @OA\Parameter(name="id", in="path", description="User ID", required=true, @OA\Schema(type="integer"))
 * )
 */
public function getUser(int $id) { }
```

**After:**
```php
#[Route('/api/user/{id}', methods: ['GET'])]
#[Hash('id')]
#[OA\Get(
    path: '/api/user/{id}',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            description: 'Encoded User ID',
            required: true,
            schema: new OA\Schema(type: 'string')
        )
    ]
)]
public function getUser(int $id) { }
```

### 8. JavaScript/Frontend Updates

If your frontend directly generates or manipulates HashId URLs:

**Before:**
```javascript
// Assuming IDs are passed as integers
function getUserUrl(userId) {
    return `/user/${userId}`;
}
```

**After:**
```javascript
// IDs should be pre-encoded by backend
function getUserUrl(encodedUserId) {
    return `/user/${encodedUserId}`;
}

// Or use an encoding library
import Hashids from 'hashids';

const hashids = new Hashids(process.env.HASHID_SALT, 10);

function getUserUrl(userId) {
    const encoded = hashids.encode(userId);
    return `/user/${encoded}`;
}
```

## Validation Checklist

After manual migration, verify:

- [ ] All custom services are properly configured
- [ ] Event subscribers/listeners work correctly
- [ ] Twig extensions function as expected
- [ ] Tests pass with new attribute syntax
- [ ] API documentation is updated
- [ ] Frontend correctly handles encoded IDs
- [ ] Configuration files are updated
- [ ] No deprecation warnings in logs

## Common Pitfalls

### 1. Forgetting to Update Service References

Always search for old service names:
```bash
grep -r "pgs_hash_id\." config/ src/ --include="*.yaml" --include="*.yml"
```

### 2. Mixed Annotation/Attribute Usage

Be consistent within each controller:
```php
// DON'T mix in same class
class Controller {
    #[Route('/new')]  // Attribute
    public function newAction() {}

    /**
     * @Route("/old")  // Annotation
     */
    public function oldAction() {}
}

// DO use one style per class
class Controller {
    #[Route('/action1')]
    public function action1() {}

    #[Route('/action2')]
    public function action2() {}
}
```

### 3. Incorrect Namespace Imports

Double-check imports:
```php
// Attributes
use Pgs\HashIdBundle\Attribute\Hash;

// NOT Annotations
use Pgs\HashIdBundle\Annotation\Hash;  // Wrong for attributes!
```

## Getting Help

If you encounter issues during manual migration:

1. Check error logs for specific messages
2. Review the [FAQ](./migration-faq.md)
3. Search existing GitHub issues
4. Create a new issue with:
   - Error messages
   - Code examples
   - Migration steps attempted

## Next Steps

After completing manual migration:

1. Run full test suite
2. Check for deprecation warnings
3. Test in staging environment
4. Plan for v5.0 preparation (removing annotation support)