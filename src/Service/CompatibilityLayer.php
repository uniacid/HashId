<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Service;

use Pgs\HashIdBundle\Annotation\Hash as HashAnnotation;
use Pgs\HashIdBundle\Attribute\Hash as HashAttribute;
use Pgs\HashIdBundle\Rector\DeprecationHandler;

/**
 * Compatibility layer for supporting both annotations and attributes.
 *
 * This service allows the bundle to work with both the legacy annotation system
 * and the modern PHP 8 attribute system during the transition period.
 */
class CompatibilityLayer
{
    private bool $deprecationWarningsEnabled;
    private bool $preferAttributes;
    
    public function __construct(
        bool $deprecationWarningsEnabled = true,
        bool $preferAttributes = true
    ) {
        $this->deprecationWarningsEnabled = $deprecationWarningsEnabled;
        $this->preferAttributes = $preferAttributes;
        
        if (!$deprecationWarningsEnabled) {
            DeprecationHandler::suppressWarnings();
        }
    }
    
    /**
     * Extract Hash configuration from a method using either annotations or attributes.
     *
     * @param \ReflectionMethod $method
     * @return array|null Array of parameter names or null if no Hash found
     */
    public function extractHashConfiguration(\ReflectionMethod $method): ?array
    {
        $parameters = null;
        
        // Try attributes first (if PHP 8+)
        if (PHP_VERSION_ID >= 80000) {
            $parameters = $this->extractFromAttributes($method);
        }
        
        // Fall back to annotations if no attributes found
        if ($parameters === null) {
            $parameters = $this->extractFromAnnotations($method);
        }
        
        return $parameters;
    }
    
    /**
     * Extract Hash configuration from PHP 8 attributes.
     *
     * @param \ReflectionMethod $method
     * @return array|null
     */
    private function extractFromAttributes(\ReflectionMethod $method): ?array
    {
        $attributes = $method->getAttributes(HashAttribute::class);
        
        if (empty($attributes)) {
            return null;
        }
        
        $attribute = $attributes[0];
        $instance = $attribute->newInstance();
        
        return $instance->getParameters();
    }
    
    /**
     * Extract Hash configuration from annotations.
     *
     * @param \ReflectionMethod $method
     * @return array|null
     */
    private function extractFromAnnotations(\ReflectionMethod $method): ?array
    {
        // This would typically use doctrine/annotations reader
        // For now, we'll parse the docblock manually as a simple implementation
        $docComment = $method->getDocComment();
        
        if ($docComment === false) {
            return null;
        }
        
        // Simple regex to find @Hash annotations
        if (preg_match('/@Hash\((.*?)\)/', $docComment, $matches)) {
            $context = sprintf(
                '%s::%s',
                $method->getDeclaringClass()->getName(),
                $method->getName()
            );
            
            // Always trigger deprecation (it will respect the suppression setting internally)
            DeprecationHandler::triggerAnnotationDeprecation(
                '@Hash',
                '#[Hash]',
                $context
            );
            
            // Parse the annotation parameters
            $params = $matches[1];
            
            // Handle single quoted string
            if (preg_match('/^"([^"]+)"$/', $params, $paramMatches)) {
                return [$paramMatches[1]];
            }
            
            // Handle array of strings
            if (preg_match('/^\{(.+)\}$/', $params, $paramMatches)) {
                $items = explode(',', $paramMatches[1]);
                return array_map(function ($item) {
                    return trim(trim($item), '"');
                }, $items);
            }
        }
        
        return null;
    }
    
    /**
     * Check if the system prefers attributes over annotations.
     *
     * @return bool
     */
    public function prefersAttributes(): bool
    {
        return $this->preferAttributes && PHP_VERSION_ID >= 80000;
    }
    
    /**
     * Enable or disable deprecation warnings.
     *
     * @param bool $enabled
     */
    public function setDeprecationWarnings(bool $enabled): void
    {
        $this->deprecationWarningsEnabled = $enabled;
        
        if ($enabled) {
            DeprecationHandler::enableWarnings();
        } else {
            DeprecationHandler::suppressWarnings();
        }
    }
    
    /**
     * Check if both annotation and attribute are present on a method.
     *
     * @param \ReflectionMethod $method
     * @return bool
     */
    public function hasDuplicateConfiguration(\ReflectionMethod $method): bool
    {
        $hasAttribute = false;
        $hasAnnotation = false;
        
        if (PHP_VERSION_ID >= 80000) {
            $hasAttribute = !empty($method->getAttributes(HashAttribute::class));
        }
        
        $docComment = $method->getDocComment();
        if ($docComment !== false) {
            $hasAnnotation = strpos($docComment, '@Hash') !== false;
        }
        
        return $hasAttribute && $hasAnnotation;
    }
    
    /**
     * Get compatibility report for a class.
     *
     * @param string $className
     * @return array
     */
    public function getCompatibilityReport(string $className): array
    {
        $report = [
            'class' => $className,
            'methods' => [],
            'uses_annotations' => false,
            'uses_attributes' => false,
            'has_duplicates' => false,
        ];
        
        $reflectionClass = new \ReflectionClass($className);
        
        foreach ($reflectionClass->getMethods() as $method) {
            $hasAttribute = false;
            $hasAnnotation = false;
            
            if (PHP_VERSION_ID >= 80000) {
                $hasAttribute = !empty($method->getAttributes(HashAttribute::class));
            }
            
            $docComment = $method->getDocComment();
            if ($docComment !== false) {
                $hasAnnotation = strpos($docComment, '@Hash') !== false;
            }
            
            if ($hasAnnotation || $hasAttribute) {
                $report['methods'][$method->getName()] = [
                    'uses_annotation' => $hasAnnotation,
                    'uses_attribute' => $hasAttribute,
                    'is_duplicate' => $hasAnnotation && $hasAttribute,
                ];
                
                if ($hasAnnotation) {
                    $report['uses_annotations'] = true;
                }
                if ($hasAttribute) {
                    $report['uses_attributes'] = true;
                }
                if ($hasAnnotation && $hasAttribute) {
                    $report['has_duplicates'] = true;
                }
            }
        }
        
        return $report;
    }
}