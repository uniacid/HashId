<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Service;

use ReflectionClass;
use Pgs\HashIdBundle\Attribute\Hash as HashAttribute;
use Pgs\HashIdBundle\Rector\DeprecationHandler;
use Pgs\HashIdBundle\Service\CompatibilityLayer;
use PHPUnit\Framework\TestCase;

class CompatibilityLayerTest extends TestCase
{
    private CompatibilityLayer $compatibilityLayer;

    protected function setUp(): void
    {
        DeprecationHandler::clearLog();
        $this->compatibilityLayer = new CompatibilityLayer(true, true);
    }

    protected function tearDown(): void
    {
        DeprecationHandler::clearLog();
        DeprecationHandler::enableWarnings();
    }

    public function testExtractFromAttributeOnly(): void
    {
        $controller = new class() {
            #[HashAttribute('id')]
            public function show(int $id): void
            {
            }
        };

        $reflectionClass = new ReflectionClass($controller);
        $method = $reflectionClass->getMethod('show');

        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);

        self::assertSame(['id'], $parameters);
        self::assertFalse(DeprecationHandler::hasDeprecation('@Hash'));
    }

    public function testExtractFromAnnotationOnly(): void
    {
        $controller = new class() {
            /**
             * @Hash("id")
             */
            public function show(int $id): void
            {
            }
        };

        $reflectionClass = new ReflectionClass($controller);
        $method = $reflectionClass->getMethod('show');

        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);

        self::assertSame(['id'], $parameters);
        self::assertTrue(DeprecationHandler::hasDeprecation('@Hash'));
    }

    public function testExtractFromAnnotationWithMultipleParameters(): void
    {
        $controller = new class() {
            /**
             * @Hash({"id", "parentId"})
             */
            public function show(int $id, int $parentId): void
            {
            }
        };

        $reflectionClass = new ReflectionClass($controller);
        $method = $reflectionClass->getMethod('show');

        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);

        self::assertSame(['id', 'parentId'], $parameters);
        self::assertTrue(DeprecationHandler::hasDeprecation('@Hash'));
    }

    public function testExtractWithBothAttributeAndAnnotation(): void
    {
        $controller = new class() {
            /**
             * @Hash("id")
             */
            #[HashAttribute('id')]
            public function show(int $id): void
            {
            }
        };

        $reflectionClass = new ReflectionClass($controller);
        $method = $reflectionClass->getMethod('show');

        // Should prefer attribute when both are present
        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);

        self::assertSame(['id'], $parameters);

        // Check for duplicate configuration
        self::assertTrue($this->compatibilityLayer->hasDuplicateConfiguration($method));
    }

    public function testExtractWithNoHashConfiguration(): void
    {
        $controller = new class() {
            public function index(): void
            {
            }
        };

        $reflectionClass = new ReflectionClass($controller);
        $method = $reflectionClass->getMethod('index');

        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);

        self::assertNull($parameters);
    }

    public function testDeprecationWarningSuppression(): void
    {
        // First create a layer with warnings disabled
        $compatibilityLayerNoWarnings = new CompatibilityLayer(false, true);

        $controller = new class() {
            /**
             * @Hash("id")
             */
            public function show(int $id): void
            {
            }
        };

        $reflectionClass = new ReflectionClass($controller);
        $method = $reflectionClass->getMethod('show');

        $compatibilityLayerNoWarnings->extractHashConfiguration($method);

        // Deprecation should still be logged even when warnings are suppressed
        self::assertTrue(DeprecationHandler::hasDeprecation('@Hash'));
        self::assertTrue(DeprecationHandler::areWarningsSuppressed());
    }

    public function testPrefersAttributes(): void
    {
        self::assertTrue($this->compatibilityLayer->prefersAttributes());

        $compatibilityLayerNoPreference = new CompatibilityLayer(true, false);
        self::assertFalse($compatibilityLayerNoPreference->prefersAttributes());
    }

    public function testCompatibilityReport(): void
    {
        $className = \get_class(new class() {
            #[HashAttribute('id')]
            public function showAttribute(int $id): void
            {
            }

            /**
             * @Hash("id")
             */
            public function showAnnotation(int $id): void
            {
            }

            /**
             * @Hash("id")
             */
            #[HashAttribute('id')]
            public function showBoth(int $id): void
            {
            }

            public function showNone(): void
            {
            }
        });

        $report = $this->compatibilityLayer->getCompatibilityReport($className);

        self::assertSame($className, $report['class']);
        self::assertTrue($report['uses_annotations']);
        self::assertTrue($report['uses_attributes']);
        self::assertTrue($report['has_duplicates']);

        self::assertArrayHasKey('showAttribute', $report['methods']);
        self::assertFalse($report['methods']['showAttribute']['uses_annotation']);
        self::assertTrue($report['methods']['showAttribute']['uses_attribute']);
        self::assertFalse($report['methods']['showAttribute']['is_duplicate']);

        self::assertArrayHasKey('showAnnotation', $report['methods']);
        self::assertTrue($report['methods']['showAnnotation']['uses_annotation']);
        self::assertFalse($report['methods']['showAnnotation']['uses_attribute']);
        self::assertFalse($report['methods']['showAnnotation']['is_duplicate']);

        self::assertArrayHasKey('showBoth', $report['methods']);
        self::assertTrue($report['methods']['showBoth']['uses_annotation']);
        self::assertTrue($report['methods']['showBoth']['uses_attribute']);
        self::assertTrue($report['methods']['showBoth']['is_duplicate']);

        self::assertArrayNotHasKey('showNone', $report['methods']);
    }
}
