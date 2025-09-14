<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Service;

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
    /**
     * Maximum allowed length for docblock to prevent ReDoS attacks.
     */
    private const MAX_DOCBLOCK_LENGTH = 10000;

    /**
     * Maximum length for parameter string in annotations.
     */
    private const MAX_PARAM_STRING_LENGTH = 500;

    /**
     * Maximum number of parameters allowed in Hash annotation.
     */
    private const MAX_PARAMETERS = 20;

    /**
     * Maximum length for a single parameter name.
     */
    private const MAX_PARAM_NAME_LENGTH = 100;

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
        
        // Early return for empty/false docblocks
        if ($docComment === false || empty($docComment)) {
            return null;
        }
        
        // Security: Validate and limit input length to prevent ReDoS attacks
        if (\strlen($docComment) > self::MAX_DOCBLOCK_LENGTH) {
            trigger_error('Docblock exceeds maximum allowed length', E_USER_WARNING);
            return null;
        }

        // Quick check if @Hash is even present before processing
        if (\strpos($docComment, '@Hash') === false) {
            return null;
        }

        // Security: Sanitize input by removing potential malicious patterns
        // Use atomic groups and possessive quantifiers to prevent catastrophic backtracking
        $docComment = \preg_replace('/\s+/', ' ', $docComment); // Normalize whitespace
        
        // Use more restrictive regex with atomic groups to prevent ReDoS
        // Pattern explanation:
        // @Hash\( - literal match for @Hash(
        // Atomic group prevents backtracking
        // [^)]++ - possessive quantifier, match non-parenthesis characters
        // \) - literal closing parenthesis
        $pattern = '/@Hash\((?>([^)]{1,' . self::MAX_PARAM_STRING_LENGTH . '}))\)/';
        if (\preg_match($pattern, $docComment, $matches)) {
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
            
            // Parse the annotation parameters with length validation
            $params = $matches[1];
            
            // Additional validation: ensure params don't contain suspicious patterns
            if (\preg_match('/[<>]|\\\\x|\\\\0/', $params)) {
                trigger_error('Invalid characters detected in Hash annotation', E_USER_WARNING);
                return null;
            }

            // Handle single quoted string with stricter pattern
            $singleParamPattern = '/^"([a-zA-Z0-9_]{1,' . self::MAX_PARAM_NAME_LENGTH . '})"$/';
            if (\preg_match($singleParamPattern, $params, $paramMatches)) {
                return [$paramMatches[1]];
            }
            
            // Handle array of strings with stricter validation
            $arrayPattern = '/^\{([^}]{1,' . self::MAX_PARAM_STRING_LENGTH . '})\}$/';
            if (\preg_match($arrayPattern, $params, $paramMatches)) {
                $items = \explode(',', $paramMatches[1]);
                
                // Validate each item and limit array size
                if (\count($items) > self::MAX_PARAMETERS) {
                    trigger_error('Too many parameters in Hash annotation', E_USER_WARNING);
                    return null;
                }

                // Single trim operation instead of redundant trim calls  
                $result = \array_map(function ($item) {
                    $trimmed = \trim($item, ' "\'');
                    // Validate parameter name format
                    if (!\preg_match('/^[a-zA-Z0-9_]+$/', $trimmed)) {
                        return null;
                    }
                    return $trimmed;
                }, $items);
                
                return \array_filter($result, function($item) {
                    return $item !== null;
                });
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