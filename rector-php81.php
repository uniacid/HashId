<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\CodeQuality\Rector\Assign\CombinedAssignRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->skip([
        // Skip test fixtures and generated files
        __DIR__ . '/tests/Fixtures',
        __DIR__ . '/var',
        __DIR__ . '/vendor',
        
        // Skip specific patterns that might need manual attention
        ChangeSwitchToMatchRector::class => [
            // Skip complex switch statements that might need manual conversion
        ],
    ]);

    // PHP 8.0: Constructor Property Promotion (fundamental modernization)
    $rectorConfig->rule(ClassPropertyAssignToConstructorPromotionRector::class);
    
    // PHP 8.0: Match Expressions (upgrade from switch when appropriate)
    $rectorConfig->rule(ChangeSwitchToMatchRector::class);
    
    // Code Quality: Combined Assignment (??= operator and others)
    $rectorConfig->rule(CombinedAssignRector::class);
    
    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__ . '/var/cache/rector');
};