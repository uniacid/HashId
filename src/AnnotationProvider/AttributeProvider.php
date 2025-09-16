<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\AnnotationProvider;

use ReflectionMethod;
use InvalidArgumentException;
use Pgs\HashIdBundle\Annotation\Hash as HashAnnotation;
use Pgs\HashIdBundle\AnnotationProvider\Dto\HashConfiguration;
use Pgs\HashIdBundle\Attribute\Hash as HashAttribute;
use Pgs\HashIdBundle\Exception\InvalidControllerException;
use Pgs\HashIdBundle\Exception\HashIdException;
use Pgs\HashIdBundle\Exception\MissingClassOrMethodException;
use Pgs\HashIdBundle\Reflection\ReflectionProvider;
use Pgs\HashIdBundle\Service\CompatibilityLayer;

/**
 * Provides Hash configuration from both attributes and annotations.
 *
 * This provider uses the CompatibilityLayer to support both modern PHP 8 attributes
 * and legacy annotations during the transition period.
 */
class AttributeProvider implements AnnotationProviderInterface
{
    public function __construct(private readonly CompatibilityLayer $compatibilityLayer, private readonly ReflectionProvider $reflectionProvider)
    {
    }

    /**
     * Get Hash configuration from a controller string.
     *
     * @param string $controller Controller string in format "Class::method"
     * @param string $annotationClassName The annotation/attribute class name to look for
     *
     * @throws InvalidControllerException If the controller string is invalid
     * @throws MissingClassOrMethodException If the class or method doesn't exist
     *
     * @return object|null The Hash configuration object or null if not found
     */
    public function getFromString(string $controller, string $annotationClassName): ?object
    {
        // Security: Check for null bytes and other malicious characters
        if (\strpos($controller, "\x00") !== false || \strpos($controller, "\r") !== false || \strpos($controller, "\n") !== false) {
            throw HashIdException::invalidController(
                $controller,
                'Contains illegal characters. Expected format: "ClassName::methodName"'
            );
        }

        // Security: Validate controller format before parsing to prevent injection
        if (!\preg_match('/^[a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*::[a-zA-Z_][a-zA-Z0-9_]*$/', $controller)) {
            throw HashIdException::invalidController(
                $controller,
                'Expected format: "ClassName::methodName"'
            );
        }

        $explodedControllerString = \explode('::', $controller);
        if (2 !== \count($explodedControllerString)) {
            $message = \sprintf('The "%s" controller is not a valid "class::method" string.', $controller);

            throw HashIdException::invalidController($controller, 'Not a valid "class::method" string');
        }

        $reflection = $this->reflectionProvider->getMethodReflectionFromClassString(...$explodedControllerString);

        // Null safety: Check if reflection is available before processing
        if ($reflection === null) {
            return null;
        }

        return $this->extractHashFromMethod($reflection, $annotationClassName);
    }

    /**
     * Get Hash configuration from a controller object.
     *
     * @param object $controller The controller object
     * @param string $method The method name
     * @param string $annotationClassName The annotation/attribute class name to look for
     *
     * @throws InvalidControllerException If the controller is not an object
     * @throws MissingClassOrMethodException If the method doesn't exist
     *
     * @return object|null The Hash configuration object or null if not found
     */
    public function getFromObject($controller, string $method, string $annotationClassName): ?object
    {
        if (!\is_object($controller)) {
            throw HashIdException::invalidController('(object)', 'Provided controller is not an object');
        }

        $reflection = $this->reflectionProvider->getMethodReflectionFromObject($controller, $method);

        // Null safety: Check if reflection is available before processing
        if ($reflection === null) {
            return null;
        }

        return $this->extractHashFromMethod($reflection, $annotationClassName);
    }

    /**
     * Extract Hash configuration from a method using the compatibility layer.
     *
     * @param ReflectionMethod|null $method The method to extract from
     * @param string $annotationClassName The annotation/attribute class name
     *
     * @return object|null The Hash configuration object or null if not found
     */
    private function extractHashFromMethod(?ReflectionMethod $method, string $annotationClassName): ?object
    {
        // Null safety check
        if ($method === null) {
            return null;
        }
        
        // Only handle Hash annotations/attributes
        if ($annotationClassName !== HashAnnotation::class && $annotationClassName !== HashAttribute::class) {
            return null;
        }

        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);

        if ($parameters === null) {
            return null;
        }

        // Return a properly typed HashConfiguration DTO
        // This maintains backward compatibility with code expecting getParameters() method
        // while providing better type safety and validation
        try {
            return new HashConfiguration($parameters);
        } catch (InvalidArgumentException $e) {
            // Log or handle invalid parameter names
            // For now, return null to maintain backward compatibility
            return null;
        }
    }
}
