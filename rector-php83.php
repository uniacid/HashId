<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

/**
 * PHP 8.3 Features Configuration
 * 
 * This is a placeholder configuration file for future PHP 8.3 modernization.
 * Will be implemented in Phase 2 of the modernization roadmap.
 * 
 * Planned features to implement:
 * - Typed class constants
 * - Dynamic class constant fetch
 * - Override attribute for inheritance
 * - Anonymous readonly classes  
 * - New json_validate() function usage
 * - Randomizer additions and improvements
 * - String manipulation improvements
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

    // TODO: Implement PHP 8.3 rules in Phase 2
    // 
    // Example rules to be added:
    // - TypedClassConstantRector::class
    // - DynamicClassConstantFetchRector::class
    // - OverrideAttributeRector::class
    // - JsonValidateFunctionRector::class
    // - AnonymousReadonlyClassRector::class
    
    // For now, this configuration does nothing
    // Uncomment and implement rules during Phase 2 modernization
    
    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__ . '/var/cache/rector');
};