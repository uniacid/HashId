<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\EventSubscriber;

use Pgs\HashIdBundle\EventSubscriber\DecodeControllerParametersSubscriber;
use Pgs\HashIdBundle\Service\DecodeControllerParameters;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class Symfony64EventSubscriberTest extends TestCase
{
    private DecodeControllerParametersSubscriber $subscriber;
    private DecodeControllerParameters $decodeService;

    protected function setUp(): void
    {
        $this->decodeService = $this->createMock(DecodeControllerParameters::class);
        $this->subscriber = new DecodeControllerParametersSubscriber($this->decodeService);
    }

    public function testImplementsEventSubscriberInterface(): void
    {
        self::assertInstanceOf(EventSubscriberInterface::class, $this->subscriber);
    }

    public function testGetSubscribedEventsReturnsCorrectEvents(): void
    {
        $subscribedEvents = DecodeControllerParametersSubscriber::getSubscribedEvents();

        // Verify it subscribes to the controller event
        self::assertArrayHasKey(KernelEvents::CONTROLLER, $subscribedEvents);

        // Verify the method and priority format for Symfony 6.4
        $eventConfig = $subscribedEvents[KernelEvents::CONTROLLER];

        // Can be either string (method name) or array [method, priority]
        if (\is_array($eventConfig)) {
            if (\is_array($eventConfig[0] ?? null)) {
                // Multiple listeners format
                foreach ($eventConfig as $config) {
                    self::assertIsArray($config);
                    self::assertIsString($config[0]); // Method name
                    if (isset($config[1])) {
                        self::assertIsInt($config[1]); // Priority
                    }
                }
            } else {
                // Single listener with priority
                self::assertIsString($eventConfig[0]); // Method name
                if (isset($eventConfig[1])) {
                    self::assertIsInt($eventConfig[1]); // Priority
                }
            }
        } else {
            // Simple string format (method name only)
            self::assertIsString($eventConfig);
        }
    }

    public function testEventSubscriberMethodExists(): void
    {
        $subscribedEvents = DecodeControllerParametersSubscriber::getSubscribedEvents();
        $eventConfig = $subscribedEvents[KernelEvents::CONTROLLER];

        // Extract method name
        $methodName = \is_array($eventConfig) ?
            (\is_array($eventConfig[0] ?? null) ? $eventConfig[0][0] : $eventConfig[0]) :
            $eventConfig;

        // Verify the method exists
        self::assertTrue(
            \method_exists($this->subscriber, $methodName),
            \sprintf('Method %s should exist in subscriber', $methodName),
        );
    }

    public function testSymfony64EventDispatcherCompatibility(): void
    {
        // Test that the subscriber works with Symfony 6.4 event dispatcher patterns
        // Note: ControllerEvent is final in Symfony 6.4+, so we can't mock it
        // Instead, we'll test the method signature

        // Get the method that handles the event
        $subscribedEvents = DecodeControllerParametersSubscriber::getSubscribedEvents();
        $eventConfig = $subscribedEvents[KernelEvents::CONTROLLER];

        $methodName = \is_array($eventConfig) ?
            (\is_array($eventConfig[0] ?? null) ? $eventConfig[0][0] : $eventConfig[0]) :
            $eventConfig;

        // The method should accept ControllerEvent (Symfony 6.4+ naming)
        $reflection = new \ReflectionMethod($this->subscriber, $methodName);
        $parameters = $reflection->getParameters();

        self::assertCount(1, $parameters);

        $paramType = $parameters[0]->getType();
        if ($paramType instanceof \ReflectionNamedType) {
            $typeName = $paramType->getName();
            // Should accept ControllerEvent or its parent classes
            self::assertTrue(
                \in_array($typeName, [
                    'Symfony\Component\HttpKernel\Event\ControllerEvent',
                    'Symfony\Component\HttpKernel\Event\KernelEvent',
                    'Symfony\Contracts\EventDispatcher\Event',
                ], true),
                \sprintf('Event parameter should be of type ControllerEvent or compatible, got %s', $typeName),
            );
        }
    }

    public function testNoDeprecatedEventMethods(): void
    {
        // Ensure we're not using deprecated event handling patterns
        $reflection = new \ReflectionClass($this->subscriber);

        // Check we're not using deprecated onKernelController naming
        // (should use getSubscribedEvents instead)
        $methods = $reflection->getMethods();
        foreach ($methods as $method) {
            if (\str_starts_with($method->getName(), 'on')) {
                // If we have onXxx methods, they should be referenced in getSubscribedEvents
                $subscribedEvents = DecodeControllerParametersSubscriber::getSubscribedEvents();
                $isReferenced = false;

                foreach ($subscribedEvents as $configs) {
                    $configArray = \is_array($configs) ? $configs : [$configs];
                    foreach ($configArray as $config) {
                        $methodName = \is_array($config) ? $config[0] : $config;
                        if ($methodName === $method->getName()) {
                            $isReferenced = true;

                            break 2;
                        }
                    }
                }

                self::assertTrue(
                    $isReferenced,
                    \sprintf('Method %s should be referenced in getSubscribedEvents', $method->getName()),
                );
            }
        }
    }

    public function testEventPriorityConfiguration(): void
    {
        $subscribedEvents = DecodeControllerParametersSubscriber::getSubscribedEvents();

        foreach ($subscribedEvents as $eventName => $configs) {
            // If priority is specified, it should be an integer
            if (\is_array($configs) && isset($configs[1])) {
                self::assertIsInt(
                    $configs[1],
                    \sprintf('Priority for event %s should be an integer', $eventName),
                );

                // Priority should be reasonable (between -255 and 255)
                self::assertGreaterThanOrEqual(
                    -255,
                    $configs[1],
                    \sprintf('Priority for event %s should be >= -255', $eventName),
                );
                self::assertLessThanOrEqual(
                    255,
                    $configs[1],
                    \sprintf('Priority for event %s should be <= 255', $eventName),
                );
            }
        }
    }
}
