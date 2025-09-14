<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

/**
 * Rector configuration for maintaining compatibility during the transition period.
 *
 * This configuration allows both annotations and attributes to coexist
 * while gradually migrating from one to the other.
 */
return static function (RectorConfig $rectorConfig): void {
    // Import the main configuration
    $rectorConfig->import(__DIR__ . '/rector.php');
    
    // Configure compatibility mode
    $rectorConfig->parameters()->set('hashid_compatibility_mode', true);
    
    // Skip automatic transformation of Hash annotations in these paths
    // to maintain backward compatibility for certain legacy code
    $rectorConfig->skip([
        __DIR__ . '/tests/Legacy',
        __DIR__ . '/src/Legacy',
        
        // Skip transformation for specific rules that need to remain as annotations
        \Rector\Php80\Rector\Class_\AnnotationToAttributeRector::class => [
            __DIR__ . '/src/Controller/DemoController.php', // Keep demo with annotations for reference
        ],
    ]);
    
    // Add custom parameters for compatibility configuration
    $rectorConfig->parameters()->set('hashid_bundle', [
        'compatibility' => [
            'maintain_annotations' => true,
            'add_attributes' => true,
            'emit_deprecations' => true,
            'dual_support_until' => '5.0',
        ],
    ]);
    
    // Register compatibility-specific rules
    $compatibilityRules = [
        // Custom rule to add attributes while keeping annotations
        \Pgs\HashIdBundle\Rector\AddAttributeKeepAnnotationRule::class,
        
        // Custom rule to add deprecation comments
        \Pgs\HashIdBundle\Rector\AddDeprecationCommentRule::class,
    ];
    
    foreach ($compatibilityRules as $rule) {
        if (class_exists($rule)) {
            $rectorConfig->rule($rule);
        }
    }
};