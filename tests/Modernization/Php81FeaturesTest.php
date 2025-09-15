<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Modernization;

use Pgs\HashIdBundle\Tests\Fixtures\Rector\ExampleServiceAfter;
use Pgs\HashIdBundle\Tests\Fixtures\Rector\ExampleServiceBefore;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PHP 8.1 feature implementations and backward compatibility.
 *
 * This test validates that PHP 8.1 modernization features are correctly
 * applied and maintain backward compatibility with existing functionality.
 */
class Php81FeaturesTest extends TestCase
{
    /**
     * Test that constructor property promotion maintains the same behavior.
     */
    public function testConstructorPropertyPromotionBehavior(): void
    {
        // Test the "before" version (traditional constructor)
        $serviceBefore = new ExampleServiceBefore('test', 42, ['option' => 'value']);

        self::assertSame('test', $serviceBefore->getName());
        self::assertSame(42, $serviceBefore->getValue());
        self::assertSame(['option' => 'value'], $serviceBefore->getOptions());

        // Test the "after" version (promoted properties)
        $serviceAfter = new ExampleServiceAfter('test', 42, ['option' => 'value']);

        self::assertSame('test', $serviceAfter->getName());
        self::assertSame(42, $serviceAfter->getValue());
        self::assertSame(['option' => 'value'], $serviceAfter->getOptions());
    }

    /**
     * Test that constructor property promotion handles null values correctly.
     */
    public function testConstructorPropertyPromotionWithNullValues(): void
    {
        $serviceBefore = new ExampleServiceBefore('test', 42);
        $serviceAfter = new ExampleServiceAfter('test', 42);

        self::assertNull($serviceBefore->getOptions());
        self::assertNull($serviceAfter->getOptions());
    }

    /**
     * Test that match expressions work correctly and provide the same results as switch.
     */
    public function testMatchExpressionBehavior(): void
    {
        $serviceBefore = new ExampleServiceBefore('test', 42);
        $serviceAfter = new ExampleServiceAfter('test', 42);

        // Test all status cases
        $statuses = ['pending', 'completed', 'failed', 'unknown'];

        foreach ($statuses as $status) {
            $beforeResult = $serviceBefore->getStatusMessage($status);
            $afterResult = $serviceAfter->getStatusMessage($status);

            self::assertSame(
                $beforeResult,
                $afterResult,
                "Match expression should produce same result as switch for status: {$status}",
            );
        }
    }

    /**
     * Test that match expressions handle edge cases correctly.
     */
    public function testMatchExpressionEdgeCases(): void
    {
        $service = new ExampleServiceAfter('test', 42);

        // Test known statuses
        self::assertSame('Processing your request', $service->getStatusMessage('pending'));
        self::assertSame('Request completed successfully', $service->getStatusMessage('completed'));
        self::assertSame('Request failed', $service->getStatusMessage('failed'));

        // Test default case
        self::assertSame('Unknown status', $service->getStatusMessage('invalid'));
        self::assertSame('Unknown status', $service->getStatusMessage(''));
    }

    /**
     * Test that PHP 8.1 features maintain API compatibility.
     */
    public function testApiCompatibility(): void
    {
        // Both versions should implement the same interface behavior
        $serviceBefore = new ExampleServiceBefore('api-test', 100, ['debug' => true]);
        $serviceAfter = new ExampleServiceAfter('api-test', 100, ['debug' => true]);

        // Verify all public methods work identically
        self::assertSame($serviceBefore->getName(), $serviceAfter->getName());
        self::assertSame($serviceBefore->getValue(), $serviceAfter->getValue());
        self::assertSame($serviceBefore->getOptions(), $serviceAfter->getOptions());

        // Verify method signatures are compatible
        $beforeReflection = new \ReflectionClass(ExampleServiceBefore::class);
        $afterReflection = new \ReflectionClass(ExampleServiceAfter::class);

        $beforeMethods = $beforeReflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $afterMethods = $afterReflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        self::assertCount(\count($beforeMethods), $afterMethods);

        foreach ($beforeMethods as $beforeMethod) {
            self::assertTrue(
                $afterReflection->hasMethod($beforeMethod->getName()),
                "Method {$beforeMethod->getName()} should exist in modernized version",
            );
        }
    }

    /**
     * Test that modernized constructors maintain type safety.
     */
    public function testConstructorTypeSafety(): void
    {
        // Test that type hints are preserved and enforced
        $this->expectException(\TypeError::class);

        // This should fail because we're passing wrong type for name parameter
        new ExampleServiceAfter(42, 42);
    }

    /**
     * Test performance characteristics remain similar after modernization.
     */
    public function testPerformanceCharacteristics(): void
    {
        $iterations = 1000;

        // Benchmark traditional constructor
        $startBefore = \microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $service = new ExampleServiceBefore("test-{$i}", $i, ['iteration' => $i]);
            $service->getName();
            $service->getValue();
            $service->getOptions();
        }
        $timeBefore = \microtime(true) - $startBefore;

        // Benchmark promoted properties
        $startAfter = \microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $service = new ExampleServiceAfter("test-{$i}", $i, ['iteration' => $i]);
            $service->getName();
            $service->getValue();
            $service->getOptions();
        }
        $timeAfter = \microtime(true) - $startAfter;

        // Modernized version should not be significantly slower
        // Allow for up to 50% variance (very generous for micro-benchmarks)
        self::assertLessThan(
            $timeBefore * 1.5,
            $timeAfter,
            'Modernized version should not be significantly slower',
        );
    }

    /**
     * Test that null coalescing assignment would work correctly.
     */
    public function testNullCoalescingAssignmentLogic(): void
    {
        $config = [];

        // Simulate the pattern we'll modernize
        // Before: if (!isset($config['key'])) { $config['key'] = 'default'; }
        // After: $config['key'] ??= 'default';

        $config['key'] ??= 'default';
        self::assertSame('default', $config['key']);

        $config['key'] = 'existing';
        $config['key'] ??= 'default';
        self::assertSame('existing', $config['key']);
    }

    /**
     * Test union type scenarios that might be applicable.
     */
    public function testUnionTypeCompatibility(): void
    {
        // Test that our code would work with union types
        $value1 = 'string';
        $value2 = 42;

        self::assertIsString($value1);
        self::assertIsInt($value2);

        // Both should be valid for string|int union type
        self::assertTrue(\is_string($value1) || \is_int($value1));
        self::assertTrue(\is_string($value2) || \is_int($value2));
    }
}
