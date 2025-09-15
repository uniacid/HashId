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

class RouterDecorator implements RouterInterface, WarmableInterface
{
    use DecoratorTrait;

    protected EncodeParametersProcessorFactory $parametersProcessorFactory;

    public function __construct(RouterInterface $router, EncodeParametersProcessorFactory $parametersProcessorFactory)
    {
        $this->object = $router;
        $this->parametersProcessorFactory = $parametersProcessorFactory;
    }

    public function getRouter(): RouterInterface
    {
        return $this->object;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function generate(
        string $name,
        array $parameters = [],
        int $referenceType = RouterInterface::ABSOLUTE_PATH,
    ): string {
        $this->processParameters($this->getRoute($name, $parameters), $parameters);

        return $this->getRouter()->generate($name, $parameters, $referenceType);
    }

    /**
     * @param array<string, mixed> $parameters
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
     * @param array<string, mixed> $parameters
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
