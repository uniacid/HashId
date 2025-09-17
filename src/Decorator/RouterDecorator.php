<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Decorator;

use Pgs\HashIdBundle\ParametersProcessor\Factory\EncodeParametersProcessorFactory;
use Pgs\HashIdBundle\Traits\DecoratorTrait;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * Router decorator that automatically encodes route parameters using Hashids.
 *
 * This decorator wraps the Symfony router to transparently encode integer
 * parameters marked with the @Hash annotation or #[Hash] attribute before
 * URL generation. It intercepts calls to generateUrl() and processes
 * parameters according to the route's Hash configuration.
 *
 * @package Pgs\HashIdBundle\Decorator
 * @since 3.0.0
 *
 * @example Usage in controllers:
 * ```php
 * // Parameters are automatically encoded when generating URLs
 * $url = $this->generateUrl('order_show', ['id' => 123]);
 * // Result: /order/4w9aA11avM
 * ```
 *
 * @example Usage in Twig templates:
 * ```twig
 * {{ url('order_show', {'id': order.id}) }}
 * {# Automatically generates: /order/4w9aA11avM #}
 * ```
 */
class RouterDecorator implements RouterInterface, WarmableInterface
{
    use DecoratorTrait;

    /**
     * Constructor.
     *
     * @param RouterInterface $router The original Symfony router to decorate
     * @param EncodeParametersProcessorFactory $parametersProcessorFactory Factory for creating parameter encoders
     */
    public function __construct(RouterInterface $router, protected EncodeParametersProcessorFactory $parametersProcessorFactory)
    {
        $this->object = $router;
    }

    /**
     * Gets the decorated router instance.
     *
     * @return RouterInterface The original router being decorated
     */
    public function getRouter(): RouterInterface
    {
        return $this->object;
    }

    /**
     * Generates a URL with automatic parameter encoding.
     *
     * Intercepts URL generation to encode integer parameters marked with
     * Hash annotations/attributes before passing them to the router.
     *
     * @param string $name The route name
     * @param array<string, mixed> $parameters Route parameters (will be encoded if marked with Hash)
     * @param int $referenceType The type of reference (absolute or relative)
     *
     * @return string The generated URL with encoded parameters
     *
     * @example
     * ```php
     * // With a route configured as: #[Hash('id', 'userId')]
     * $url = $router->generate('user_profile', [
     *     'id' => 123,
     *     'userId' => 456,
     *     'tab' => 'settings'
     * ]);
     * // Result: /user/abc123/profile/def456?tab=settings
     * // Only 'id' and 'userId' are encoded, 'tab' remains unchanged
     * ```
     */
    public function generate(
        string $name,
        array $parameters = [],
        int $referenceType = RouterInterface::ABSOLUTE_PATH,
    ): string {
        // Skip processing for system routes (profiler, assets, etc.)
        if (!str_starts_with($name, '_')) {
            $this->processParameters($this->getRoute($name, $parameters), $parameters);
        }

        return $this->getRouter()->generate($name, $parameters, $referenceType);
    }

    /**
     * Retrieves a route by name, with locale fallback support.
     *
     * @param string $name The route name
     * @param array<string, mixed> $parameters Route parameters (used for locale detection)
     *
     * @return Route|null The route instance or null if not found
     */
    private function getRoute(string $name, array $parameters): ?Route
    {
        $routeCollection = $this->getRouter()->getRouteCollection();
        $route = $routeCollection->get($name);

        if (null === $route) {
            $locale = $parameters['_locale'] ?? $this->getRouter()->getContext()->getParameter('_locale');
            $route = $routeCollection->get(\sprintf('%s.%s', $name, $locale));
        }

        return $route;
    }

    /**
     * Processes parameters for encoding based on route configuration.
     *
     * @param Route|null $route The route to process parameters for
     * @param array<string, mixed> $parameters Parameters to encode (passed by reference)
     *
     * @return void
     */
    private function processParameters(?Route $route, array &$parameters): void
    {
        if (null !== $route) {
            $parametersProcessor = $this->parametersProcessorFactory->createRouteEncodeParametersProcessor($route);
            if ($parametersProcessor->needToProcess()) {
                $parameters = $parametersProcessor->process($parameters);
            }
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function setContext(RequestContext $context): void
    {
        $this->getRouter()->setContext($context);
    }

    /**
     * @codeCoverageIgnore
     */
    public function getContext(): RequestContext
    {
        return $this->getRouter()->getContext();
    }

    /**
     * @codeCoverageIgnore
     */
    public function getRouteCollection(): RouteCollection
    {
        return $this->getRouter()->getRouteCollection();
    }

    /**
     * @codeCoverageIgnore
     *
     * @return array<string, mixed>
     */
    public function match(string $pathinfo): array
    {
        return $this->getRouter()->match($pathinfo);
    }

    /**
     * @codeCoverageIgnore
     * @return array<string, mixed>
     */
    public function warmUp(string $cacheDir): array
    {
        if ($this->getRouter() instanceof WarmableInterface) {
            return $this->getRouter()->warmUp($cacheDir);
        }
        
        return [];
    }
}
