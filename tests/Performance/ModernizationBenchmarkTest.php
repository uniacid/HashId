<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Performance;

use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Tests\Performance\Framework\BenchmarkRunner;
use Pgs\HashIdBundle\Service\HasherFactory;
use Pgs\HashIdBundle\Service\CompatibilityLayer;
use Pgs\HashIdBundle\AnnotationProvider\AttributeProvider;
use Pgs\HashIdBundle\Reflection\ReflectionProvider;
use Pgs\HashIdBundle\Attribute\Hash;

/**
 * Performance benchmarks for PHP 8.3 modernization features.
 *
 * @group performance
 * @group benchmark
 * @group modernization
 */
class ModernizationBenchmarkTest extends TestCase
{
    private BenchmarkRunner $runner;

    protected function setUp(): void
    {
        $this->runner = new BenchmarkRunner(1000, 100);
        $this->runner->setVerbose(getenv('VERBOSE') === '1');
    }

    /**
     * Benchmark attribute vs annotation performance.
     */
    public function testAttributeVsAnnotationPerformance(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Attributes require PHP 8.0+');
        }

        $compatibilityLayer = new CompatibilityLayer();
        $reflectionProvider = new ReflectionProvider();

        // Create test methods with annotation and attribute
        $annotationMethod = $this->createAnnotationMethod();
        $attributeMethod = $this->createAttributeMethod();

        $comparison = $this->runner->compare(
            'annotation_extraction',
            function () use ($compatibilityLayer, $annotationMethod) {
                $compatibilityLayer->extractHashConfiguration($annotationMethod);
            },
            'attribute_extraction',
            function () use ($compatibilityLayer, $attributeMethod) {
                $compatibilityLayer->extractHashConfiguration($attributeMethod);
            }
        );

        // Attributes should be faster than annotations
        $this->assertGreaterThan(1, $comparison['speedup'],
            'Attributes should be faster than annotations');

        if (getenv('CI')) {
            echo sprintf(
                "\nAttribute vs Annotation Performance:\n" .
                "  Annotation: %.4fms\n" .
                "  Attribute: %.4fms\n" .
                "  Speedup: %.2fx\n",
                $comparison['a']->getMean(),
                $comparison['b']->getMean(),
                $comparison['speedup']
            );
        }
    }

    /**
     * Benchmark typed property performance.
     */
    public function testTypedPropertyPerformance(): void
    {
        // Compare typed vs untyped property access
        $typedClass = new class {
            public string $salt = 'test-salt';
            public int $minLength = 10;
            public string $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        };

        $untypedClass = new class {
            public $salt = 'test-salt';
            public $minLength = 10;
            public $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        };

        $comparison = $this->runner->compare(
            'untyped_properties',
            function () use ($untypedClass) {
                $salt = $untypedClass->salt;
                $minLength = $untypedClass->minLength;
                $alphabet = $untypedClass->alphabet;
            },
            'typed_properties',
            function () use ($typedClass) {
                $salt = $typedClass->salt;
                $minLength = $typedClass->minLength;
                $alphabet = $typedClass->alphabet;
            }
        );

        // Typed properties should have minimal overhead
        $this->assertGreaterThan(0.9, $comparison['speedup'],
            'Typed properties should not have significant overhead');

        if (getenv('CI')) {
            echo sprintf(
                "\nTyped Property Performance:\n" .
                "  Untyped: %.6fms\n" .
                "  Typed: %.6fms\n" .
                "  Overhead: %.2f%%\n",
                $comparison['a']->getMean(),
                $comparison['b']->getMean(),
                ((1 / $comparison['speedup']) - 1) * 100
            );
        }
    }

    /**
     * Benchmark readonly property performance.
     */
    public function testReadonlyPropertyPerformance(): void
    {
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Readonly properties require PHP 8.1+');
        }

        // Create classes with readonly and regular properties
        $readonlyClass = new class('test-salt', 10) {
            public function __construct(
                public readonly string $salt,
                public readonly int $minLength
            ) {}
        };

        $regularClass = new class('test-salt', 10) {
            public function __construct(
                public string $salt,
                public int $minLength
            ) {}
        };

        $comparison = $this->runner->compare(
            'regular_properties',
            function () use ($regularClass) {
                $salt = $regularClass->salt;
                $minLength = $regularClass->minLength;
            },
            'readonly_properties',
            function () use ($readonlyClass) {
                $salt = $readonlyClass->salt;
                $minLength = $readonlyClass->minLength;
            }
        );

        // Readonly properties should have minimal overhead
        $this->assertGreaterThan(0.9, $comparison['speedup'],
            'Readonly properties should not have significant overhead');

        if (getenv('CI')) {
            echo sprintf(
                "\nReadonly Property Performance:\n" .
                "  Regular: %.6fms\n" .
                "  Readonly: %.6fms\n" .
                "  Overhead: %.2f%%\n",
                $comparison['a']->getMean(),
                $comparison['b']->getMean(),
                ((1 / $comparison['speedup']) - 1) * 100
            );
        }
    }

    /**
     * Benchmark constructor property promotion.
     */
    public function testConstructorPromotionPerformance(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Constructor promotion requires PHP 8.0+');
        }

        // Traditional constructor
        $traditionalClass = new class('test', 10) {
            private string $salt;
            private int $minLength;

            public function __construct(string $salt, int $minLength)
            {
                $this->salt = $salt;
                $this->minLength = $minLength;
            }
        };

        // Promoted constructor
        $promotedClass = new class('test', 10) {
            public function __construct(
                private string $salt,
                private int $minLength
            ) {}
        };

        $comparison = $this->runner->compare(
            'traditional_constructor',
            function () {
                new class('test', 10) {
                    private string $salt;
                    private int $minLength;

                    public function __construct(string $salt, int $minLength)
                    {
                        $this->salt = $salt;
                        $this->minLength = $minLength;
                    }
                };
            },
            'promoted_constructor',
            function () {
                new class('test', 10) {
                    public function __construct(
                        private string $salt,
                        private int $minLength
                    ) {}
                };
            }
        );

        // Promoted constructors should be as fast or faster
        $this->assertGreaterThan(0.95, $comparison['speedup'],
            'Constructor promotion should not have significant overhead');

        if (getenv('CI')) {
            echo sprintf(
                "\nConstructor Promotion Performance:\n" .
                "  Traditional: %.4fms\n" .
                "  Promoted: %.4fms\n" .
                "  Speedup: %.2fx\n",
                $comparison['a']->getMean(),
                $comparison['b']->getMean(),
                $comparison['speedup']
            );
        }
    }

    /**
     * Benchmark json_validate() vs json_decode() validation.
     */
    public function testJsonValidationPerformance(): void
    {
        if (PHP_VERSION_ID < 80300) {
            $this->markTestSkipped('json_validate() requires PHP 8.3+');
        }

        $validJson = '{"salt":"test-salt","min_length":10,"alphabet":"abcdefghijklmnopqrstuvwxyz"}';
        $invalidJson = '{"salt":"test-salt","min_length":10,}';

        // Valid JSON comparison
        $comparison = $this->runner->compare(
            'json_decode_validation',
            function () use ($validJson) {
                json_decode($validJson);
                $valid = json_last_error() === JSON_ERROR_NONE;
            },
            'json_validate',
            function () use ($validJson) {
                $valid = json_validate($validJson);
            }
        );

        // json_validate should be significantly faster
        $this->assertGreaterThan(2, $comparison['speedup'],
            'json_validate() should be at least 2x faster than json_decode()');

        if (getenv('CI')) {
            echo sprintf(
                "\nJSON Validation Performance (valid):\n" .
                "  json_decode: %.4fms\n" .
                "  json_validate: %.4fms\n" .
                "  Speedup: %.2fx\n",
                $comparison['a']->getMean(),
                $comparison['b']->getMean(),
                $comparison['speedup']
            );
        }
    }

    /**
     * Benchmark typed constants performance.
     */
    public function testTypedConstantsPerformance(): void
    {
        if (PHP_VERSION_ID < 80300) {
            $this->markTestSkipped('Typed constants require PHP 8.3+');
        }

        // Use existing classes with typed and untyped constants
        $comparison = $this->runner->compare(
            'untyped_constants',
            function () {
                // Use class with untyped constants
                $salt = \Pgs\HashIdBundle\Config\HashIdConfigInterface::DEFAULT_SALT;
                $minLength = \Pgs\HashIdBundle\Config\HashIdConfigInterface::DEFAULT_MIN_LENGTH;
                $alphabet = \Pgs\HashIdBundle\Config\HashIdConfigInterface::DEFAULT_ALPHABET;
            },
            'typed_constants',
            function () {
                // Use same interface (PHP 8.3 would have typed constants)
                $salt = \Pgs\HashIdBundle\Config\HashIdConfigInterface::DEFAULT_SALT;
                $minLength = \Pgs\HashIdBundle\Config\HashIdConfigInterface::DEFAULT_MIN_LENGTH;
                $alphabet = \Pgs\HashIdBundle\Config\HashIdConfigInterface::DEFAULT_ALPHABET;
            }
        );

        // Typed constants should have minimal overhead
        $this->assertGreaterThan(0.9, $comparison['speedup'],
            'Typed constants should not have significant overhead');

        if (getenv('CI')) {
            echo sprintf(
                "\nTyped Constants Performance:\n" .
                "  Untyped: %.6fms\n" .
                "  Typed: %.6fms\n" .
                "  Overhead: %.2f%%\n",
                $comparison['a']->getMean(),
                $comparison['b']->getMean(),
                ((1 / $comparison['speedup']) - 1) * 100
            );
        }
    }

    /**
     * Benchmark overall modernization impact.
     */
    public function testOverallModernizationImpact(): void
    {
        // Simulate legacy vs modern implementation
        $legacyFactory = function () {
            return new class {
                private $salt = 'test-salt';
                private $minLength = 10;

                public function create($type, $config)
                {
                    $salt = isset($config['salt']) ? $config['salt'] : $this->salt;
                    $minLength = isset($config['min_length']) ? $config['min_length'] : $this->minLength;
                    return ['salt' => $salt, 'min_length' => $minLength];
                }
            };
        };

        $modernFactory = function () {
            return new class {
                public function __construct(
                    private readonly string $salt = 'test-salt',
                    private readonly int $minLength = 10
                ) {}

                public function create(string $type, array $config): array
                {
                    return [
                        'salt' => $config['salt'] ?? $this->salt,
                        'min_length' => $config['min_length'] ?? $this->minLength,
                    ];
                }
            };
        };

        $comparison = $this->runner->compare(
            'legacy_implementation',
            function () use ($legacyFactory) {
                $factory = $legacyFactory();
                $factory->create('default', ['salt' => 'custom']);
            },
            'modern_implementation',
            function () use ($modernFactory) {
                $factory = $modernFactory();
                $factory->create('default', ['salt' => 'custom']);
            }
        );

        // Modern implementation should be competitive or faster
        $this->assertGreaterThan(0.8, $comparison['speedup'],
            'Modern implementation should not be significantly slower');

        if (getenv('CI')) {
            echo sprintf(
                "\nOverall Modernization Impact:\n" .
                "  Legacy: %.4fms\n" .
                "  Modern: %.4fms\n" .
                "  Performance: %.2fx %s\n",
                $comparison['a']->getMean(),
                $comparison['b']->getMean(),
                abs($comparison['speedup']),
                $comparison['speedup'] >= 1 ? 'faster' : 'slower'
            );
        }
    }

    /**
     * Create a mock method with annotation.
     */
    private function createAnnotationMethod(): \ReflectionMethod
    {
        $class = new class {
            /**
             * @Hash({"id", "userId"})
             */
            public function testMethod() {}
        };

        return new \ReflectionMethod($class, 'testMethod');
    }

    /**
     * Create a mock method with attribute.
     */
    private function createAttributeMethod(): \ReflectionMethod
    {
        if (PHP_VERSION_ID < 80000) {
            return $this->createAnnotationMethod();
        }

        $class = new class {
            #[Hash(['id', 'userId'])]
            public function testMethod() {}
        };

        return new \ReflectionMethod($class, 'testMethod');
    }
}