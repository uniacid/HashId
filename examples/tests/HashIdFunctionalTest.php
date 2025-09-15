<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Examples\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Process\Process;

class HashIdFunctionalTest extends TestCase
{
    private string $examplesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->examplesDir = dirname(__DIR__);
    }

    /**
     * @group functional
     */
    public function testHashIdEncodingInUrls(): void
    {
        $expectedTests = [
            'symfony-6.4' => [
                'port' => 8064,
                'routes' => [
                    '/order/1' => 'should redirect or encode to hash',
                    '/product/list' => 'should work without hashing',
                    '/user/profile/123' => 'should encode user ID',
                ],
            ],
            'symfony-7.0' => [
                'port' => 8070,
                'routes' => [
                    '/order/1' => 'should redirect or encode to hash',
                    '/product/list' => 'should work without hashing',
                    '/user/profile/456' => 'should encode user ID',
                ],
            ],
        ];

        foreach ($expectedTests as $version => $config) {
            $appDir = $this->examplesDir . '/' . $version;

            if (!file_exists($appDir . '/public/index.php')) {
                $this->markTestSkipped("{$version} example application not yet created");
                continue;
            }

            $this->assertApplicationHashIdWorks($appDir, $config['port'], $config['routes']);
        }
    }

    private function assertApplicationHashIdWorks(string $appDir, int $port, array $routes): void
    {
        // Start development server
        $serverProcess = new Process(
            ['php', '-S', "localhost:{$port}", '-t', 'public'],
            $appDir
        );
        $serverProcess->start();

        // Give server time to start
        sleep(2);

        try {
            $client = HttpClient::create();

            foreach ($routes as $route => $expectation) {
                $response = $client->request('GET', "http://localhost:{$port}{$route}", [
                    'max_redirects' => 0,
                    'http_errors' => false,
                ]);

                $statusCode = $response->getStatusCode();
                $headers = $response->getHeaders(false);

                // Check if HashId is working (either through redirect or direct encoding)
                if (str_contains($route, '/1') || str_contains($route, '/123') || str_contains($route, '/456')) {
                    // For routes with IDs, we expect either:
                    // 1. A redirect to the hashed version
                    // 2. Direct rendering with hashed URLs in content

                    if ($statusCode === 301 || $statusCode === 302) {
                        $location = $headers['location'][0] ?? '';
                        $this->assertNotEquals(
                            $route,
                            parse_url($location, PHP_URL_PATH),
                            'Redirected URL should have encoded ID'
                        );
                        $this->assertDoesNotMatchRegularExpression(
                            '/\/\d+$/',
                            $location,
                            'URL should not end with raw integer'
                        );
                    } elseif ($statusCode === 200) {
                        $content = $response->getContent();
                        // Check that generated URLs in content are hashed
                        $this->assertStringNotContainsString(
                            'href="/order/1"',
                            $content,
                            'Generated URLs should be hashed'
                        );
                    }
                }
            }
        } finally {
            $serverProcess->stop();
        }
    }

    /**
     * @group functional
     */
    public function testTwigIntegration(): void
    {
        $templates = [
            'symfony-6.4/templates/order/show.html.twig',
            'symfony-7.0/templates/order/show.html.twig',
        ];

        foreach ($templates as $templatePath) {
            $fullPath = $this->examplesDir . '/' . $templatePath;

            if (!file_exists($fullPath)) {
                continue;
            }

            $content = file_get_contents($fullPath);

            // Check for proper Twig usage
            $this->assertStringContainsString(
                "{{ url('order_show', {'id': order.id}) }}",
                $content,
                'Template should use url() function with raw IDs'
            );

            $this->assertStringContainsString(
                "{{ path('order_edit', {'id': order.id}) }}",
                $content,
                'Template should use path() function with raw IDs'
            );
        }
    }

    /**
     * @group functional
     */
    public function testControllerIntegration(): void
    {
        $controllers = [
            'symfony-6.4/src/Controller/OrderController.php',
            'symfony-7.0/src/Controller/OrderController.php',
        ];

        foreach ($controllers as $controllerPath) {
            $fullPath = $this->examplesDir . '/' . $controllerPath;

            if (!file_exists($fullPath)) {
                continue;
            }

            $content = file_get_contents($fullPath);
            $version = str_contains($controllerPath, '6.4') ? '6.4' : '7.0';

            if ($version === '6.4') {
                // Symfony 6.4 should support both annotations and attributes
                $this->assertTrue(
                    str_contains($content, '@Hash') || str_contains($content, '#[Hash'),
                    'Symfony 6.4 controller should use Hash annotation or attribute'
                );
            } else {
                // Symfony 7.0 should prefer attributes
                $this->assertStringContainsString(
                    '#[Hash',
                    $content,
                    'Symfony 7.0 controller should use Hash attribute'
                );
            }

            // Check for proper method signatures
            $this->assertMatchesRegularExpression(
                '/public function \w+\([^)]*\$id[^)]*\)/',
                $content,
                'Controller methods should accept $id parameter'
            );

            // Check for proper redirect usage
            $this->assertStringContainsString(
                '$this->redirectToRoute',
                $content,
                'Controller should use redirectToRoute for navigation'
            );
        }
    }

    /**
     * @group functional
     */
    public function testDoctrineIntegration(): void
    {
        $entities = [
            'symfony-6.4/src/Entity/Order.php',
            'symfony-7.0/src/Entity/Order.php',
        ];

        foreach ($entities as $entityPath) {
            $fullPath = $this->examplesDir . '/' . $entityPath;

            if (!file_exists($fullPath)) {
                continue;
            }

            $content = file_get_contents($fullPath);

            // Check for proper entity setup
            $this->assertStringContainsString('#[ORM\Entity', $content);
            $this->assertStringContainsString('#[ORM\Id', $content);
            $this->assertStringContainsString('#[ORM\GeneratedValue', $content);
            $this->assertStringContainsString('private ?int $id', $content);
        }
    }

    /**
     * @group migration
     */
    public function testMigrationExample(): void
    {
        $migrationDir = $this->examplesDir . '/migration-example';

        if (!is_dir($migrationDir)) {
            $this->markTestSkipped('Migration example not yet created');
            return;
        }

        // Test that Rector configuration exists
        $rectorConfig = $migrationDir . '/rector.php';
        if (file_exists($rectorConfig)) {
            $content = file_get_contents($rectorConfig);
            $this->assertStringContainsString('HashAnnotationToAttributeRector', $content);
        }

        // Test migration script
        $migrationScript = $migrationDir . '/migrate.sh';
        if (file_exists($migrationScript)) {
            $this->assertFileIsExecutable($migrationScript);

            $content = file_get_contents($migrationScript);
            $this->assertStringContainsString('vendor/bin/rector', $content);
            $this->assertStringContainsString('--dry-run', $content);
        }
    }
}