<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Documentation;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;

/**
 * Tests PHPDoc completeness and quality across the HashId bundle.
 *
 * This test ensures all public API elements are properly documented
 * to support developers migrating from v3.x to v4.x.
 */
class DocblockValidationTest extends TestCase
{
    private const SRC_PATH = __DIR__ . '/../../src';
    private const MIN_CLASS_DOC_LENGTH = 20;
    private const MIN_METHOD_DOC_LENGTH = 10;

    /**
     * @var array<string, array{hasClassDoc: bool, methods: array}>
     */
    private array $documentationReport = [];

    /**
     * Tests that all classes have class-level documentation.
     */
    public function testAllClassesHaveDocumentation(): void
    {
        $classes = $this->getAllClasses();
        $undocumentedClasses = [];

        foreach ($classes as $className) {
            $reflection = new ReflectionClass($className);
            $docComment = $reflection->getDocComment();

            if ($docComment === false || strlen(trim($docComment)) < self::MIN_CLASS_DOC_LENGTH) {
                $undocumentedClasses[] = $className;
            }

            $this->documentationReport[$className] = [
                'hasClassDoc' => $docComment !== false,
                'methods' => []
            ];
        }

        $this->assertEmpty(
            $undocumentedClasses,
            sprintf(
                "The following classes lack proper documentation:\n%s",
                implode("\n", array_map(fn($c) => "  - $c", $undocumentedClasses))
            )
        );
    }

    /**
     * Tests that all public methods have proper PHPDoc blocks.
     */
    public function testPublicMethodsHaveDocumentation(): void
    {
        $classes = $this->getAllClasses();
        $undocumentedMethods = [];

        foreach ($classes as $className) {
            $reflection = new ReflectionClass($className);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                // Skip inherited methods
                if ($method->getDeclaringClass()->getName() !== $className) {
                    continue;
                }

                // Skip magic methods except __construct
                if (str_starts_with($method->getName(), '__') && $method->getName() !== '__construct') {
                    continue;
                }

                $docComment = $method->getDocComment();
                $hasDoc = $docComment !== false && strlen(trim($docComment)) > self::MIN_METHOD_DOC_LENGTH;

                if (!$hasDoc) {
                    $undocumentedMethods[] = sprintf('%s::%s()', $className, $method->getName());
                }

                $this->documentationReport[$className]['methods'][$method->getName()] = $hasDoc;
            }
        }

        $this->assertEmpty(
            $undocumentedMethods,
            sprintf(
                "The following public methods lack documentation:\n%s",
                implode("\n", array_map(fn($m) => "  - $m", $undocumentedMethods))
            )
        );
    }

    /**
     * Tests that method documentation includes required annotations.
     */
    public function testMethodDocumentationHasRequiredAnnotations(): void
    {
        $classes = $this->getAllClasses();
        $methodsWithIncompleteDoc = [];

        foreach ($classes as $className) {
            $reflection = new ReflectionClass($className);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $className) {
                    continue;
                }

                if (str_starts_with($method->getName(), '__') && $method->getName() !== '__construct') {
                    continue;
                }

                $docComment = $method->getDocComment();
                if ($docComment === false) {
                    continue;
                }

                $issues = $this->validateMethodDocumentation($method, $docComment);
                if (!empty($issues)) {
                    $methodsWithIncompleteDoc[sprintf('%s::%s()', $className, $method->getName())] = $issues;
                }
            }
        }

        if (!empty($methodsWithIncompleteDoc)) {
            $message = "The following methods have incomplete documentation:\n";
            foreach ($methodsWithIncompleteDoc as $method => $issues) {
                $message .= sprintf("  %s:\n", $method);
                foreach ($issues as $issue) {
                    $message .= sprintf("    - %s\n", $issue);
                }
            }
            $this->fail($message);
        }
    }

    /**
     * Tests that key public API methods include usage examples.
     */
    public function testKeyMethodsHaveUsageExamples(): void
    {
        $keyMethods = [
            'Pgs\HashIdBundle\Decorator\RouterDecorator::generateUrl',
            'Pgs\HashIdBundle\Service\HasherFactory::createHasher',
            'Pgs\HashIdBundle\ParametersProcessor\Encode::process',
            'Pgs\HashIdBundle\ParametersProcessor\Decode::process',
        ];

        $methodsWithoutExamples = [];

        foreach ($keyMethods as $methodPath) {
            [$className, $methodName] = explode('::', $methodPath);

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if (!$reflection->hasMethod($methodName)) {
                continue;
            }

            $method = $reflection->getMethod($methodName);
            $docComment = $method->getDocComment();

            if ($docComment === false || !str_contains($docComment, '@example') && !str_contains(strtolower($docComment), 'example:')) {
                $methodsWithoutExamples[] = $methodPath;
            }
        }

        $this->assertEmpty(
            $methodsWithoutExamples,
            sprintf(
                "The following key API methods lack usage examples:\n%s",
                implode("\n", array_map(fn($m) => "  - $m", $methodsWithoutExamples))
            )
        );
    }

    /**
     * Tests documentation coverage percentage meets minimum requirements.
     */
    public function testDocumentationCoverageMeetsMinimum(): void
    {
        $this->testAllClassesHaveDocumentation();
        $this->testPublicMethodsHaveDocumentation();

        $totalClasses = count($this->documentationReport);
        $documentedClasses = count(array_filter($this->documentationReport, fn($r) => $r['hasClassDoc']));

        $totalMethods = 0;
        $documentedMethods = 0;

        foreach ($this->documentationReport as $classReport) {
            $totalMethods += count($classReport['methods']);
            $documentedMethods += count(array_filter($classReport['methods']));
        }

        $classCoverage = $totalClasses > 0 ? ($documentedClasses / $totalClasses) * 100 : 100;
        $methodCoverage = $totalMethods > 0 ? ($documentedMethods / $totalMethods) * 100 : 100;
        $overallCoverage = ($classCoverage + $methodCoverage) / 2;

        $this->assertGreaterThanOrEqual(
            90,
            $overallCoverage,
            sprintf(
                "Documentation coverage is below 90%%. Current coverage: %.2f%% (Classes: %.2f%%, Methods: %.2f%%)",
                $overallCoverage,
                $classCoverage,
                $methodCoverage
            )
        );
    }

    /**
     * Validates that method documentation contains required elements.
     *
     * @param ReflectionMethod $method The method to validate
     * @param string $docComment The documentation comment
     * @return array<string> List of validation issues
     */
    private function validateMethodDocumentation(ReflectionMethod $method, string $docComment): array
    {
        $issues = [];
        $parameters = $method->getParameters();

        // Check for parameter documentation
        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            if (!preg_match('/@param\s+[^\s]+\s+\$' . preg_quote($paramName, '/') . '/', $docComment)) {
                $issues[] = sprintf('Missing @param for $%s', $paramName);
            }
        }

        // Check for return documentation (unless method returns void)
        $returnType = $method->getReturnType();
        $isVoid = false;

        if ($returnType !== null) {
            if ($returnType instanceof \ReflectionNamedType) {
                $isVoid = $returnType->getName() === 'void';
            }
        }

        if (!$isVoid) {
            if (!str_contains($docComment, '@return')) {
                $issues[] = 'Missing @return annotation';
            }
        }

        // Check for throws documentation if method can throw exceptions
        // This is a basic check - could be enhanced with static analysis
        $methodBody = $this->getMethodBody($method);
        if ($methodBody && str_contains($methodBody, 'throw ')) {
            if (!str_contains($docComment, '@throws')) {
                $issues[] = 'Method throws exceptions but missing @throws annotation';
            }
        }

        return $issues;
    }

    /**
     * Gets the body of a method as a string.
     *
     * @param ReflectionMethod $method
     * @return string|null
     */
    private function getMethodBody(ReflectionMethod $method): ?string
    {
        $filename = $method->getFileName();
        if (!$filename) {
            return null;
        }

        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        if (!$startLine || !$endLine) {
            return null;
        }

        $source = file($filename);
        if ($source === false) {
            return null;
        }

        $body = implode('', array_slice($source, $startLine - 1, $endLine - $startLine + 1));

        return $body;
    }

    /**
     * Gets all classes in the source directory.
     *
     * @return array<string>
     */
    private function getAllClasses(): array
    {
        $finder = new Finder();
        $finder->files()
            ->in(self::SRC_PATH)
            ->name('*.php')
            ->notPath('Tests')
            ->notPath('vendor');

        $classes = [];

        foreach ($finder as $file) {
            $content = file_get_contents($file->getRealPath());

            // Extract namespace
            if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
                $namespace = $namespaceMatch[1];

                // Extract class/interface/trait name
                if (preg_match('/(?:class|interface|trait)\s+([^\s{]+)/', $content, $classMatch)) {
                    $className = $classMatch[1];
                    $fullClassName = $namespace . '\\' . $className;

                    if (class_exists($fullClassName) || interface_exists($fullClassName) || trait_exists($fullClassName)) {
                        $classes[] = $fullClassName;
                    }
                }
            }
        }

        return $classes;
    }
}