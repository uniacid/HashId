<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

/**
 * PHP 8.2 Features Configuration
 * 
 * This is a placeholder configuration file for future PHP 8.2 modernization.
 * Will be implemented in Phase 2 of the modernization roadmap.
 * 
 * Planned features to implement:
 * - Readonly classes
 * - Null, false, and true as standalone types  
 * - New in initializers
 * - Disjunctive Normal Form (DNF) types
 * - Sensitive parameter attribute
 * - Random extension improvements
 */
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/tests/Fixtures',
        __DIR__ . '/var',
        __DIR__ . '/vendor',
    ]);

    // TODO: Implement PHP 8.2 rules in Phase 2
    // 
    // Example rules to be added:
    // - ReadOnlyClassRector::class
    // - StandaloneNullTrueFalseRector::class  
    // - NewInInitializerRector::class (extended version)
    // - SensitiveParameterRector::class
    
    // For now, this configuration does nothing
    // Uncomment and implement rules during Phase 2 modernization
    
    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__ . '/var/cache/rector');
};