<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Functional;

use Pgs\HashIdBundle\Attribute\Hash;
use Pgs\HashIdBundle\Service\CompatibilityLayer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class AttributeRoutingTest extends TestCase
{
    /**
     * Test controller using PHP 8 attributes.
     */
    public function testControllerWithAttribute(): void
    {
        $controller = new class() {
            #[Hash('id')]
            public function show(int $id): Response
            {
                return new Response('Product ID: ' . $id);
            }

            #[Hash(['productId', 'categoryId'])]
            public function showInCategory(int $productId, int $categoryId): Response
            {
                return new Response("Product: {$productId} in Category: {$categoryId}");
            }
        };

        $reflectionClass = new \ReflectionClass($controller);
        $compatibilityLayer = new CompatibilityLayer();

        // Test single parameter attribute
        $showMethod = $reflectionClass->getMethod('show');
        $parameters = $compatibilityLayer->extractHashConfiguration($showMethod);
        self::assertSame(['id'], $parameters);

        // Test multiple parameters attribute
        $categoryMethod = $reflectionClass->getMethod('showInCategory');
        $parameters = $compatibilityLayer->extractHashConfiguration($categoryMethod);
        self::assertSame(['productId', 'categoryId'], $parameters);
    }

    /**
     * Test controller using legacy annotations.
     */
    public function testControllerWithAnnotation(): void
    {
        $controller = new class() {
            /**
             * @Hash("id")
             */
            public function show(int $id): Response
            {
                return new Response('Product ID: ' . $id);
            }

            /**
             * @Hash({"productId", "categoryId"})
             */
            public function showInCategory(int $productId, int $categoryId): Response
            {
                return new Response("Product: {$productId} in Category: {$categoryId}");
            }
        };

        $reflectionClass = new \ReflectionClass($controller);
        $compatibilityLayer = new CompatibilityLayer();

        // Test single parameter annotation
        $showMethod = $reflectionClass->getMethod('show');
        $parameters = $compatibilityLayer->extractHashConfiguration($showMethod);
        self::assertSame(['id'], $parameters);

        // Test multiple parameters annotation
        $categoryMethod = $reflectionClass->getMethod('showInCategory');
        $parameters = $compatibilityLayer->extractHashConfiguration($categoryMethod);
        self::assertSame(['productId', 'categoryId'], $parameters);
    }

    /**
     * Test controller mixing attributes and annotations (migration scenario).
     */
    public function testControllerWithMixedApproaches(): void
    {
        $controller = new class() {
            #[Hash('id')]
            public function newMethod(int $id): Response
            {
                return new Response('Using attribute: ' . $id);
            }

            /**
             * @Hash("id")
             */
            public function legacyMethod(int $id): Response
            {
                return new Response('Using annotation: ' . $id);
            }

            /**
             * @Hash("id")
             */
            #[Hash('id')]
            public function transitionMethod(int $id): Response
            {
                return new Response('Using both: ' . $id);
            }
        };

        $reflectionClass = new \ReflectionClass($controller);
        $compatibilityLayer = new CompatibilityLayer();

        // All methods should extract the same parameter
        foreach (['newMethod', 'legacyMethod', 'transitionMethod'] as $methodName) {
            $method = $reflectionClass->getMethod($methodName);
            $parameters = $compatibilityLayer->extractHashConfiguration($method);
            self::assertSame(['id'], $parameters, "Failed for method: {$methodName}");
        }

        // Check for duplicate configuration
        $transitionMethod = $reflectionClass->getMethod('transitionMethod');
        self::assertTrue($compatibilityLayer->hasDuplicateConfiguration($transitionMethod));
    }

    /**
     * Test attribute on controller class.
     */
    public function testClassLevelAttribute(): void
    {
        // Hash attribute now supports both CLASS and METHOD targets

        $controller = new #[Hash(['globalId'])] class {
            public function show(int $globalId): Response
            {
                return new Response('Global ID: ' . $globalId);
            }
        };

        $reflectionClass = new \ReflectionClass($controller);
        $classAttributes = $reflectionClass->getAttributes(Hash::class);

        // Class-level attributes are now supported
        self::assertCount(1, $classAttributes);

        $hashAttribute = $classAttributes[0]->newInstance();
        self::assertSame(['globalId'], $hashAttribute->getParameters());
    }

    /**
     * Test complex parameter patterns.
     */
    public function testComplexParameterPatterns(): void
    {
        $controller = new class() {
            #[Hash(['orderId', 'customerId', 'productId'])]
            public function complexRoute(
                int $orderId,
                int $customerId,
                int $productId,
                string $action,  // This one is not hashed
            ): Response {
                return new Response("Order: {$orderId}, Customer: {$customerId}, Product: {$productId}, Action: {$action}");
            }
        };

        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod('complexRoute');

        $compatibilityLayer = new CompatibilityLayer();
        $parameters = $compatibilityLayer->extractHashConfiguration($method);

        self::assertSame(['orderId', 'customerId', 'productId'], $parameters);
        self::assertNotContains('action', $parameters);
    }
}
