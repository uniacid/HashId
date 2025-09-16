<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Performance;

use Symfony\Component\Routing\RouteCollection;
use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Tests\Performance\Framework\BenchmarkRunner;
use Pgs\HashIdBundle\Decorator\RouterDecorator;
use Pgs\HashIdBundle\Service\DecodeControllerParameters;
use Pgs\HashIdBundle\ParametersProcessor\ParametersProcessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Performance benchmarks for router decoration and URL generation.
 *
 * @group performance
 * @group benchmark
 * @group routing
 */
class RoutingBenchmarkTest extends TestCase
{
    private BenchmarkRunner $runner;

    protected function setUp(): void
    {
        $this->runner = new BenchmarkRunner(1000, 100);
        $this->runner->setVerbose(getenv('VERBOSE') === '1');
    }

    /**
     * Benchmark router decoration overhead.
     */
    public function testRouterDecorationOverhead(): void
    {
        $baseRouter = $this->createMockRouter();
        $processor = $this->createMockProcessor();
        $decodeService = $this->createMockDecodeService();

        $decoratedRouter = new RouterDecorator($baseRouter, $processor, $decodeService);

        // Test URL generation without hashing
        $comparison = $this->runner->compare(
            'direct_router',
            function () use ($baseRouter) {
                $baseRouter->generate('test_route', ['id' => 123]);
            },
            'decorated_router',
            function () use ($decoratedRouter) {
                $decoratedRouter->generate('test_route', ['id' => 123]);
            }
        );

        // Decoration overhead should be minimal (less than 5%)
        $this->assertLessThan(1.05, 1 / $comparison['speedup'],
            'Router decoration overhead exceeds 5%');

        if (getenv('CI')) {
            echo sprintf(
                "\nRouter Decoration Overhead:\n" .
                "  Direct: %.4fms\n" .
                "  Decorated: %.4fms\n" .
                "  Overhead: %.2f%%\n",
                $comparison['a']->getMean(),
                $comparison['b']->getMean(),
                ((1 / $comparison['speedup']) - 1) * 100
            );
        }
    }

    /**
     * Benchmark URL generation with parameter encoding.
     */
    public function testUrlGenerationWithEncoding(): void
    {
        $baseRouter = $this->createMockRouter();
        $processor = $this->createMockProcessor(true); // Will encode parameters
        $decodeService = $this->createMockDecodeService();

        $decoratedRouter = new RouterDecorator($baseRouter, $processor, $decodeService);

        $result = $this->runner->benchmark('url_with_encoding', function () use ($decoratedRouter) {
            $decoratedRouter->generate('test_route', ['id' => 12345, 'userId' => 67890]);
        });

        // Should generate URLs with encoding in under 0.5ms
        $this->assertLessThan(0.5, $result->getMean(),
            sprintf('URL generation with encoding too slow: %.4fms', $result->getMean()));

        if (getenv('CI')) {
            echo sprintf(
                "\nURL Generation with Encoding:\n" .
                "  Mean: %.4fms\n" .
                "  95th percentile: %.4fms\n" .
                "  Ops/sec: %.0f\n",
                $result->getMean(),
                $result->getPercentile95(),
                $result->getOpsPerSecond()
            );
        }
    }

    /**
     * Benchmark batch URL generation.
     */
    public function testBatchUrlGeneration(): void
    {
        $baseRouter = $this->createMockRouter();
        $processor = $this->createMockProcessor(true);
        $decodeService = $this->createMockDecodeService();

        $decoratedRouter = new RouterDecorator($baseRouter, $processor, $decodeService);

        // Generate 100 different URLs
        $routes = [];
        for ($i = 0; $i < 100; $i++) {
            $routes[] = [
                'name' => 'route_' . ($i % 10),
                'params' => ['id' => $i * 100, 'page' => $i % 5],
            ];
        }

        $result = $this->runner->benchmark('batch_url_generation', function () use ($decoratedRouter, $routes) {
            foreach ($routes as $route) {
                $decoratedRouter->generate($route['name'], $route['params']);
            }
        });

        // Should generate 100 URLs in under 20ms
        $this->assertLessThan(20, $result->getMean(),
            sprintf('Batch URL generation too slow: %.4fms for 100 URLs', $result->getMean()));

        if (getenv('CI')) {
            echo sprintf(
                "\nBatch URL Generation (100 URLs):\n" .
                "  Total: %.4fms\n" .
                "  Per URL: %.4fms\n" .
                "  Throughput: %.0f URLs/sec\n",
                $result->getMean(),
                $result->getMean() / 100,
                (1000 / $result->getMean()) * 100
            );
        }
    }

    /**
     * Benchmark parameter decoding in request processing.
     */
    public function testParameterDecodingPerformance(): void
    {
        $decodeService = new DecodeControllerParameters($this->createMockProcessor(false));

        // Create mock request with encoded parameters
        $request = $this->createMockRequest([
            'id' => 'encoded123',
            'userId' => 'encoded456',
            'page' => '2',
        ]);

        $result = $this->runner->benchmark('parameter_decoding', function () use ($decodeService, $request) {
            $decodeService->decodeControllerParameters($request, 'TestController::testAction');
        });

        // Should decode parameters in under 0.2ms
        $this->assertLessThan(0.2, $result->getMean(),
            sprintf('Parameter decoding too slow: %.4fms', $result->getMean()));

        if (getenv('CI')) {
            echo sprintf(
                "\nParameter Decoding Performance:\n" .
                "  Mean: %.4fms\n" .
                "  Median: %.4fms\n" .
                "  Ops/sec: %.0f\n",
                $result->getMean(),
                $result->getMedian(),
                $result->getOpsPerSecond()
            );
        }
    }

    /**
     * Benchmark complex route generation.
     */
    public function testComplexRouteGeneration(): void
    {
        $baseRouter = $this->createMockRouter();
        $processor = $this->createMockProcessor(true);
        $decodeService = $this->createMockDecodeService();

        $decoratedRouter = new RouterDecorator($baseRouter, $processor, $decodeService);

        // Complex route with many parameters
        $complexParams = [
            'id' => 12345,
            'userId' => 67890,
            'categoryId' => 111,
            'page' => 5,
            'sort' => 'name',
            'filter' => 'active',
            'limit' => 20,
        ];

        $result = $this->runner->benchmark('complex_route', function () use ($decoratedRouter, $complexParams) {
            $decoratedRouter->generate('complex_route', $complexParams);
        });

        // Complex routes should still be fast (under 1ms)
        $this->assertLessThan(1, $result->getMean(),
            sprintf('Complex route generation too slow: %.4fms', $result->getMean()));

        if (getenv('CI')) {
            echo sprintf(
                "\nComplex Route Generation:\n" .
                "  Mean: %.4fms\n" .
                "  Max: %.4fms\n" .
                "  Parameters: %d\n",
                $result->getMean(),
                $result->getMax(),
                count($complexParams)
            );
        }
    }

    /**
     * Benchmark memory usage for route generation.
     */
    public function testRouteGenerationMemory(): void
    {
        $baseRouter = $this->createMockRouter();
        $processor = $this->createMockProcessor(true);
        $decodeService = $this->createMockDecodeService();

        $decoratedRouter = new RouterDecorator($baseRouter, $processor, $decodeService);

        $memoryProfile = $this->runner->profileMemory(function () use ($decoratedRouter) {
            for ($i = 0; $i < 1000; $i++) {
                $decoratedRouter->generate('test_route', ['id' => $i, 'page' => $i % 10]);
            }
        }, 10);

        // Should use less than 5MB for 10,000 route generations
        $this->assertLessThan(
            5 * 1024 * 1024,
            $memoryProfile['used'],
            sprintf('Route generation uses too much memory: %s', $this->formatBytes($memoryProfile['used']))
        );

        if (getenv('CI')) {
            echo sprintf(
                "\nRoute Generation Memory (10,000 operations):\n" .
                "  Memory used: %s\n" .
                "  Per operation: %s\n",
                $this->formatBytes($memoryProfile['used']),
                $this->formatBytes($memoryProfile['used'] / 10000)
            );
        }
    }

    /**
     * Benchmark caching impact on route generation.
     */
    public function testRouteCachingImpact(): void
    {
        $baseRouter = $this->createMockRouter();
        $processor = $this->createMockProcessor(true);
        $decodeService = $this->createMockDecodeService();

        $decoratedRouter = new RouterDecorator($baseRouter, $processor, $decodeService);

        // First generation (cold cache)
        $coldResult = $this->runner->benchmark('cold_cache', function () use ($decoratedRouter) {
            // Clear any internal caches
            $decoratedRouter->generate('new_route_' . rand(), ['id' => 123]);
        });

        // Repeated generation (warm cache)
        $warmResult = $this->runner->benchmark('warm_cache', function () use ($decoratedRouter) {
            $decoratedRouter->generate('cached_route', ['id' => 123]);
        });

        // Warm cache should be faster
        $speedup = $coldResult->getMean() / $warmResult->getMean();
        $this->assertGreaterThan(1, $speedup,
            'Cached route generation should be faster');

        if (getenv('CI')) {
            echo sprintf(
                "\nRoute Caching Impact:\n" .
                "  Cold cache: %.4fms\n" .
                "  Warm cache: %.4fms\n" .
                "  Speedup: %.2fx\n",
                $coldResult->getMean(),
                $warmResult->getMean(),
                $speedup
            );
        }
    }

    /**
     * Create a mock router.
     */
    private function createMockRouter(): RouterInterface
    {
        $router = $this->createMock(RouterInterface::class);

        $router->method('generate')
            ->willReturnCallback(function ($name, $params = []) {
                $query = http_build_query($params);
                return "/$name" . ($query ? "?$query" : '');
            });

        $router->method('getContext')
            ->willReturn(new RequestContext());

        $router->method('setContext')
            ->willReturnSelf();

        $router->method('getRouteCollection')
            ->willReturn(new RouteCollection());

        $router->method('match')
            ->willReturn(['_route' => 'test_route']);

        return $router;
    }

    /**
     * Create a mock parameters processor.
     */
    private function createMockProcessor(bool $willEncode = false): ParametersProcessorInterface
    {
        $processor = $this->createMock(ParametersProcessorInterface::class);

        // Use the actual interface method 'process'
        $processor->method('process')
            ->willReturnCallback(function ($params) use ($willEncode) {
                if (!$willEncode) {
                    return $params;
                }

                $encoded = [];
                foreach ($params as $key => $value) {
                    if (in_array($key, ['id', 'userId', 'categoryId'])) {
                        $encoded[$key] = 'encoded' . $value;
                    } else {
                        $encoded[$key] = $value;
                    }
                }
                return $encoded;
            });

        // Set up other interface methods
        $processor->method('needToProcess')->willReturn($willEncode);
        $processor->method('setParametersToProcess')->willReturnSelf();
        $processor->method('getParametersToProcess')->willReturn(['id', 'userId', 'categoryId']);

        return $processor;
    }

    /**
     * Create a mock decode service.
     */
    private function createMockDecodeService(): DecodeControllerParameters
    {
        return $this->createMock(DecodeControllerParameters::class);
    }

    /**
     * Create a mock request.
     */
    private function createMockRequest(array $attributes = []): object
    {
        $request = new class($attributes) {
            public $attributes;

            public function __construct(array $attributes)
            {
                $this->attributes = new class($attributes) {
                    public function __construct(private array $data)
                    {
                    }

                    public function all(): array
                    {
                        return $this->data;
                    }

                    public function set(string $key, $value): void
                    {
                        $this->data[$key] = $value;
                    }
                };
            }
        };

        return $request;
    }

    /**
     * Format bytes to human readable.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $value = abs($bytes);

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        return sprintf('%.2f %s', $value, $units[$unitIndex]);
    }
}