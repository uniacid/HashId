<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Php83\Rector\ClassConst\AddTypeToConstRector;

/**
 * PHP 8.3 Features Configuration
 * 
 * Implements PHP 8.3 modernization features for the HashId Bundle.
 * This configuration applies PHP 8.3 specific transformations including:
 * - Typed class constants
 * - Override attribute for inherited methods
 * - Anonymous readonly classes
 * - json_validate() function usage
 */
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/tests/Fixtures',
        __DIR__ . '/tests/App',
        __DIR__ . '/var',
        __DIR__ . '/vendor',
    ]);

    // Import PHP 8.3 rules from Rector
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83,
    ]);
    
    // Additional PHP 8.3 specific rules
    $rectorConfig->rule(AddOverrideAttributeToOverriddenMethodsRector::class);
    
    // Note: Some PHP 8.3 features require manual implementation:
    // - Typed class constants (no automatic Rector rule yet)
    // - Dynamic class constant fetch (syntax-specific, manual implementation)
    // - json_validate() replacement (context-specific logic)
    // - Anonymous readonly classes (manual for test fixtures)
    
    // Configure Rector for optimal performance
    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__ . '/var/cache/rector');
    
    // Import names for cleaner code
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
};