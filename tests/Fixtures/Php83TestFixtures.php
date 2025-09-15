<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Fixtures;

use Pgs\HashIdBundle\Annotation\Hash;
use Pgs\HashIdBundle\ParametersProcessor\Converter\ConverterInterface;
use Pgs\HashIdBundle\ParametersProcessor\ParametersProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * PHP 8.3 test fixtures using anonymous readonly classes.
 *
 * Provides test doubles and mock objects using PHP 8.3's
 * anonymous readonly class feature for improved test isolation.
 *
 * @since 4.0.0
 */
class Php83TestFixtures
{
    /**
     * Create a mock converter using anonymous readonly class (PHP 8.3).
     *
     * @param string $prefix Prefix for encoded values
     */
    public static function createMockConverter(string $prefix = 'encoded_'): ConverterInterface
    {
        return new readonly class($prefix) implements ConverterInterface {
            public function __construct(
                private string $prefix,
            ) {
            }

            public function encode(mixed $value): string
            {
                if (!\is_numeric($value)) {
                    return (string) $value;
                }

                return $this->prefix . $value;
            }

            public function decode(string $value): mixed
            {
                if (!\str_starts_with($value, $this->prefix)) {
                    return $value;
                }

                $decoded = \mb_substr($value, \mb_strlen($this->prefix));

                return \is_numeric($decoded) ? (int) $decoded : $decoded;
            }
        };
    }

    /**
     * Create a mock parameters processor using anonymous readonly class.
     *
     * @param array<string, mixed> $processedParams
     */
    public static function createMockParametersProcessor(array $processedParams = []): ParametersProcessorInterface
    {
        return new readonly class($processedParams, []) implements ParametersProcessorInterface {
            public function __construct(
                private array $params,
                private array $parametersToProcess = [],
            ) {
            }

            public function process(array $parameters): array
            {
                return \array_merge($parameters, $this->params);
            }

            public function setParametersToProcess(array $parametersToProcess): self
            {
                // Can't modify readonly property, so we return self unchanged
                // This is a mock, so exact behavior isn't critical
                return $this;
            }

            public function getParametersToProcess(): array
            {
                return $this->parametersToProcess;
            }

            public function needToProcess(): bool
            {
                return !empty($this->parametersToProcess);
            }
        };
    }

    /**
     * Create a mock Hash annotation using anonymous readonly class.
     *
     * @param array<string>|string $parameters
     */
    public static function createMockHashAnnotation(array|string $parameters): object
    {
        return new readonly class($parameters) {
            public function __construct(
                private array|string $parameters,
            ) {
            }

            public function getParameters(): array
            {
                return \is_array($this->parameters) ? $this->parameters : [$this->parameters];
            }
        };
    }

    /**
     * Create a mock request with route parameters.
     *
     * @param array<string, mixed> $routeParams
     */
    public static function createMockRequest(array $routeParams = [], string $method = 'GET'): Request
    {
        $request = Request::create('/test', $method);

        // Use anonymous readonly class for route params container
        $paramsContainer = new readonly class($routeParams) {
            public function __construct(
                private array $params,
            ) {
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->params[$key] ?? $default;
            }

            public function all(): array
            {
                return $this->params;
            }
        };

        $request->attributes->set('_route_params', $routeParams);

        return $request;
    }

    /**
     * Create a mock controller with Hash annotations.
     *
     * @param array<string, array<string>> $methodAnnotations
     */
    public static function createMockController(array $methodAnnotations = []): object
    {
        return new readonly class($methodAnnotations) {
            public function __construct(
                private array $annotations,
            ) {
            }

            public function getHashParameters(string $method): array
            {
                return $this->annotations[$method] ?? [];
            }

            public function hasMethod(string $method): bool
            {
                return isset($this->annotations[$method]);
            }

            #[\Override]
            public function __toString(): string
            {
                return 'MockController';
            }
        };
    }

    /**
     * Create a mock hashids configuration.
     */
    public static function createMockHashidsConfig(
        string $salt = '',
        int $minLength = 10,
        string $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
    ): object {
        return new readonly class($salt, $minLength, $alphabet) {
            public function __construct(
                public readonly string $salt,
                public readonly int $minLength,
                public readonly string $alphabet,
            ) {
            }

            public function toArray(): array
            {
                return [
                    'salt' => $this->salt,
                    'min_length' => $this->minLength,
                    'alphabet' => $this->alphabet,
                ];
            }
        };
    }

    /**
     * Create a mock route collection for testing.
     *
     * @param array<string, array{path: string, methods: array<string>}> $routes
     */
    public static function createMockRouteCollection(array $routes = []): object
    {
        return new readonly class($routes) {
            public function __construct(
                private array $routes,
            ) {
            }

            public function get(string $name): ?array
            {
                return $this->routes[$name] ?? null;
            }

            public function has(string $name): bool
            {
                return isset($this->routes[$name]);
            }

            public function all(): array
            {
                return $this->routes;
            }

            public function count(): int
            {
                return \count($this->routes);
            }
        };
    }

    /**
     * Create a mock event for testing event subscribers.
     */
    public static function createMockControllerEvent(Request $request, ?object $controller = null): object
    {
        return new readonly class($request, $controller) {
            public function __construct(
                private Request $request,
                private ?object $controller,
            ) {
            }

            public function getRequest(): Request
            {
                return $this->request;
            }

            public function getController(): ?object
            {
                return $this->controller;
            }

            public function hasController(): bool
            {
                return $this->controller !== null;
            }
        };
    }

    /**
     * Create a mock annotation reader for testing.
     *
     * @param array<string, array<object>> $annotations
     */
    public static function createMockAnnotationReader(array $annotations = []): object
    {
        return new readonly class($annotations) {
            public function __construct(
                private array $annotations,
            ) {
            }

            public function getMethodAnnotations(\ReflectionMethod $method): array
            {
                $key = $method->class . '::' . $method->name;

                return $this->annotations[$key] ?? [];
            }

            public function getClassAnnotations(\ReflectionClass $class): array
            {
                return $this->annotations[$class->name] ?? [];
            }

            public function getMethodAnnotation(\ReflectionMethod $method, string $annotationClass): ?object
            {
                $annotations = $this->getMethodAnnotations($method);

                foreach ($annotations as $annotation) {
                    if ($annotation instanceof $annotationClass) {
                        return $annotation;
                    }
                }

                return null;
            }
        };
    }
}
