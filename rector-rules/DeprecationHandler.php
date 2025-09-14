<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Rector;

/**
 * Handles deprecation notices for the HashId Bundle modernization.
 *
 * This class provides a centralized way to manage deprecation warnings
 * during the transition from annotations to attributes.
 */
class DeprecationHandler
{
    private const DEPRECATION_VERSION = '4.0';
    private const REMOVAL_VERSION = '5.0';
    
    private static bool $suppressWarnings = false;
    private static array $deprecationLog = [];
    
    /**
     * Trigger a deprecation notice for annotation usage.
     *
     * @param string $annotation The annotation being deprecated
     * @param string $replacement The recommended replacement
     * @param string|null $context Additional context (e.g., class and method)
     */
    public static function triggerAnnotationDeprecation(
        string $annotation,
        string $replacement,
        ?string $context = null
    ): void {
        $message = sprintf(
            'Using %s annotation is deprecated since HashId Bundle %s and will be removed in %s. Use %s instead.',
            $annotation,
            self::DEPRECATION_VERSION,
            self::REMOVAL_VERSION,
            $replacement
        );
        
        if ($context !== null) {
            $message .= sprintf(' (in %s)', $context);
        }
        
        self::trigger($message, $annotation);
    }
    
    /**
     * Trigger a deprecation notice for a specific feature.
     *
     * @param string $feature The feature being deprecated
     * @param string $alternative The recommended alternative
     */
    public static function triggerFeatureDeprecation(
        string $feature,
        string $alternative
    ): void {
        $message = sprintf(
            '%s is deprecated since HashId Bundle %s and will be removed in %s. %s',
            $feature,
            self::DEPRECATION_VERSION,
            self::REMOVAL_VERSION,
            $alternative
        );
        
        self::trigger($message, $feature);
    }
    
    /**
     * Trigger a deprecation notice.
     *
     * @param string $message The deprecation message
     * @param string $key A unique key for this deprecation
     */
    private static function trigger(string $message, string $key): void
    {
        // Log the deprecation
        if (!isset(self::$deprecationLog[$key])) {
            self::$deprecationLog[$key] = [
                'message' => $message,
                'count' => 0,
                'first_triggered' => time(),
            ];
        }
        self::$deprecationLog[$key]['count']++;
        self::$deprecationLog[$key]['last_triggered'] = time();
        
        // Trigger the deprecation if not suppressed
        if (!self::$suppressWarnings) {
            @trigger_error($message, E_USER_DEPRECATED);
        }
    }
    
    /**
     * Suppress deprecation warnings.
     *
     * Useful for testing or when running in legacy mode.
     */
    public static function suppressWarnings(): void
    {
        self::$suppressWarnings = true;
    }
    
    /**
     * Enable deprecation warnings.
     */
    public static function enableWarnings(): void
    {
        self::$suppressWarnings = false;
    }
    
    /**
     * Check if warnings are suppressed.
     */
    public static function areWarningsSuppressed(): bool
    {
        return self::$suppressWarnings;
    }
    
    /**
     * Get the deprecation log.
     *
     * @return array<string, array{message: string, count: int, first_triggered: int, last_triggered?: int}>
     */
    public static function getDeprecationLog(): array
    {
        return self::$deprecationLog;
    }
    
    /**
     * Clear the deprecation log.
     */
    public static function clearLog(): void
    {
        self::$deprecationLog = [];
    }
    
    /**
     * Get a summary of all deprecations.
     *
     * @return string
     */
    public static function getSummary(): string
    {
        if (empty(self::$deprecationLog)) {
            return 'No deprecations triggered.';
        }
        
        $summary = "Deprecation Summary:\n";
        $summary .= str_repeat('=', 50) . "\n\n";
        
        foreach (self::$deprecationLog as $key => $info) {
            $summary .= sprintf(
                "- %s\n  Triggered %d time(s)\n  %s\n\n",
                $key,
                $info['count'],
                $info['message']
            );
        }
        
        $summary .= str_repeat('=', 50) . "\n";
        $summary .= sprintf(
            "Total: %d unique deprecations\n",
            count(self::$deprecationLog)
        );
        
        return $summary;
    }
    
    /**
     * Check if a specific deprecation has been triggered.
     *
     * @param string $key The deprecation key
     * @return bool
     */
    public static function hasDeprecation(string $key): bool
    {
        return isset(self::$deprecationLog[$key]);
    }
}