<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Documentation;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Tests API documentation coverage and quality metrics.
 *
 * This test ensures comprehensive documentation coverage for the public API,
 * supporting developers using and migrating to the HashId Bundle v4.x.
 */
class ApiCoverageTest extends TestCase
{
    private const CORE_API_CLASSES = [
        'Pgs\HashIdBundle\PgsHashIdBundle',
        'Pgs\HashIdBundle\Decorator\RouterDecorator',
        'Pgs\HashIdBundle\Attribute\Hash',
        'Pgs\HashIdBundle\Annotation\Hash',
        'Pgs\HashIdBundle\Service\HasherFactory',
        'Pgs\HashIdBundle\Service\CompatibilityLayer',
        'Pgs\HashIdBundle\ParametersProcessor\Encode',
        'Pgs\HashIdBundle\ParametersProcessor\Decode',
        'Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter',
    ];

    /**
     * Tests that all core API classes are fully documented.
     */
    public function testCoreApiClassesAreFullyDocumented(): void
    {
        $undocumentedElements = [];

        foreach (self::CORE_API_CLASSES as $className) {
            if (!class_exists($className)) {
                $this->markTestSkipped("Class $className does not exist");
                continue;
            }

            $reflection = new ReflectionClass($className);

            // Check class documentation
            if (!$reflection->getDocComment()) {
                $undocumentedElements[] = sprintf('Class: %s', $className);
            }

            // Check public methods
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $className) {
                    continue;
                }

                if (!$method->getDocComment()) {
                    $undocumentedElements[] = sprintf('Method: %s::%s()', $className, $method->getName());
                }
            }

            // Check public properties
            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                if ($property->getDeclaringClass()->getName() !== $className) {
                    continue;
                }

                if (!$property->getDocComment()) {
                    $undocumentedElements[] = sprintf('Property: %s::$%s', $className, $property->getName());
                }
            }
        }

        $this->assertEmpty(
            $undocumentedElements,
            sprintf(
                "Core API elements lack documentation:\n%s",
                implode("\n", array_map(fn($e) => "  - $e", $undocumentedElements))
            )
        );
    }

    /**
     * Tests that interfaces have comprehensive documentation.
     */
    public function testInterfacesAreFullyDocumented(): void
    {
        $interfaces = [
            'Pgs\HashIdBundle\Config\HashIdConfigInterface',
            'Pgs\HashIdBundle\ParametersProcessor\ParametersProcessorInterface',
            'Pgs\HashIdBundle\ParametersProcessor\Converter\ConverterInterface',
            'Pgs\HashIdBundle\AnnotationProvider\AnnotationProviderInterface',
            'Pgs\HashIdBundle\Service\HasherInterface',
        ];

        $undocumentedMethods = [];

        foreach ($interfaces as $interfaceName) {
            if (!interface_exists($interfaceName)) {
                continue;
            }

            $reflection = new ReflectionClass($interfaceName);

            // Check interface documentation
            if (!$reflection->getDocComment()) {
                $undocumentedMethods[] = sprintf('Interface: %s', $interfaceName);
            }

            // Check all methods (interfaces only have public methods)
            foreach ($reflection->getMethods() as $method) {
                $doc = $method->getDocComment();
                if (!$doc) {
                    $undocumentedMethods[] = sprintf('%s::%s()', $interfaceName, $method->getName());
                } else {
                    // Check for complete documentation
                    $params = $method->getParameters();
                    foreach ($params as $param) {
                        if (!str_contains($doc, '@param') || !str_contains($doc, '$' . $param->getName())) {
                            $undocumentedMethods[] = sprintf(
                                '%s::%s() - missing @param for $%s',
                                $interfaceName,
                                $method->getName(),
                                $param->getName()
                            );
                        }
                    }

                    if (!str_contains($doc, '@return')) {
                        $undocumentedMethods[] = sprintf(
                            '%s::%s() - missing @return',
                            $interfaceName,
                            $method->getName()
                        );
                    }
                }
            }
        }

        $this->assertEmpty(
            $undocumentedMethods,
            sprintf(
                "Interface methods lack complete documentation:\n%s",
                implode("\n", array_map(fn($m) => "  - $m", $undocumentedMethods))
            )
        );
    }

    /**
     * Tests that deprecated elements are properly marked.
     */
    public function testDeprecatedElementsAreMarked(): void
    {
        $elementsNeedingDeprecation = [
            'Pgs\HashIdBundle\Annotation\Hash' => 'Use Attribute\Hash instead',
            'Pgs\HashIdBundle\AnnotationProvider\AnnotationProvider' => 'Legacy annotation support',
        ];

        $unmarkedDeprecations = [];

        foreach ($elementsNeedingDeprecation as $className => $reason) {
            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            $doc = $reflection->getDocComment();

            if (!$doc || !str_contains($doc, '@deprecated')) {
                $unmarkedDeprecations[] = sprintf('%s (%s)', $className, $reason);
            }
        }

        $this->assertEmpty(
            $unmarkedDeprecations,
            sprintf(
                "Elements that should be marked as deprecated:\n%s",
                implode("\n", array_map(fn($e) => "  - $e", $unmarkedDeprecations))
            )
        );
    }

    /**
     * Tests that migration-critical classes have migration notes.
     */
    public function testMigrationNotesExist(): void
    {
        $classesNeedingMigrationNotes = [
            'Pgs\HashIdBundle\Attribute\Hash' => 'New PHP 8 attribute',
            'Pgs\HashIdBundle\Service\HasherFactory' => 'Multiple hasher support',
            'Pgs\HashIdBundle\Service\CompatibilityLayer' => 'Backward compatibility',
        ];

        $missingMigrationNotes = [];

        foreach ($classesNeedingMigrationNotes as $className => $feature) {
            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            $doc = $reflection->getDocComment();

            if (!$doc || (!str_contains(strtolower($doc), 'migration') && !str_contains($doc, '@since'))) {
                $missingMigrationNotes[] = sprintf('%s - %s', $className, $feature);
            }
        }

        $this->assertEmpty(
            $missingMigrationNotes,
            sprintf(
                "Classes missing migration notes:\n%s",
                implode("\n", array_map(fn($c) => "  - $c", $missingMigrationNotes))
            )
        );
    }

    /**
     * Tests that documentation includes type information for PHP 8.3.
     */
    public function testDocumentationIncludesModernTypes(): void
    {
        $coreClasses = self::CORE_API_CLASSES;
        $methodsWithoutTypes = [];

        foreach ($coreClasses as $className) {
            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $className) {
                    continue;
                }

                $doc = $method->getDocComment();
                if (!$doc) {
                    continue;
                }

                // Check for typed parameters in documentation
                if (str_contains($doc, '@param')) {
                    if (preg_match_all('/@param\s+(\S+)/', $doc, $matches)) {
                        foreach ($matches[1] as $type) {
                            // Check if type is just 'mixed' or missing type info
                            if ($type === 'mixed' || $type === '$' || str_starts_with($type, '$')) {
                                $methodsWithoutTypes[] = sprintf(
                                    '%s::%s() has untyped @param',
                                    $className,
                                    $method->getName()
                                );
                            }
                        }
                    }
                }

                // Check for typed return in documentation
                if (str_contains($doc, '@return')) {
                    if (preg_match('/@return\s+(\S+)/', $doc, $match)) {
                        $returnType = $match[1];
                        if ($returnType === 'mixed') {
                            $methodsWithoutTypes[] = sprintf(
                                '%s::%s() has mixed @return type',
                                $className,
                                $method->getName()
                            );
                        }
                    }
                }
            }
        }

        // We expect some mixed types, but not too many
        $mixedTypeCount = count($methodsWithoutTypes);
        $this->assertLessThanOrEqual(
            10,
            $mixedTypeCount,
            sprintf(
                "Too many methods with unspecific types (%d found). Consider using more specific types:\n%s",
                $mixedTypeCount,
                implode("\n", array_slice($methodsWithoutTypes, 0, 10))
            )
        );
    }

    /**
     * Generates a documentation coverage report.
     */
    public function testGenerateDocumentationReport(): void
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'coverage' => [],
            'summary' => [
                'total_classes' => 0,
                'documented_classes' => 0,
                'total_methods' => 0,
                'documented_methods' => 0,
            ]
        ];

        foreach (self::CORE_API_CLASSES as $className) {
            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            $classDoc = $reflection->getDocComment() !== false;

            $methods = [];
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $className) {
                    continue;
                }

                $hasDoc = $method->getDocComment() !== false;
                $methods[$method->getName()] = $hasDoc;

                $report['summary']['total_methods']++;
                if ($hasDoc) {
                    $report['summary']['documented_methods']++;
                }
            }

            $report['coverage'][$className] = [
                'has_class_doc' => $classDoc,
                'methods' => $methods,
            ];

            $report['summary']['total_classes']++;
            if ($classDoc) {
                $report['summary']['documented_classes']++;
            }
        }

        // Calculate percentages
        $classPercentage = $report['summary']['total_classes'] > 0
            ? round(($report['summary']['documented_classes'] / $report['summary']['total_classes']) * 100, 2)
            : 0;

        $methodPercentage = $report['summary']['total_methods'] > 0
            ? round(($report['summary']['documented_methods'] / $report['summary']['total_methods']) * 100, 2)
            : 0;

        $report['summary']['class_coverage_percentage'] = $classPercentage;
        $report['summary']['method_coverage_percentage'] = $methodPercentage;
        $report['summary']['overall_coverage_percentage'] = round(($classPercentage + $methodPercentage) / 2, 2);

        // Output report for CI/debugging
        $this->addToAssertionCount(1);

        // Assert minimum coverage
        $this->assertGreaterThanOrEqual(
            80,
            $report['summary']['overall_coverage_percentage'],
            sprintf(
                "Documentation coverage is below 80%%. Current: %.2f%% (Classes: %.2f%%, Methods: %.2f%%)",
                $report['summary']['overall_coverage_percentage'],
                $classPercentage,
                $methodPercentage
            )
        );
    }
}