<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->skip([
        // Skip test fixtures
        __DIR__ . '/tests/Fixtures',
    ]);

    // Import modular configurations
    // Note: These will be loaded conditionally based on what modernization phase is being applied
    
    // Phase 1: PHP 8.1 features (current focus)
    // Uncomment to apply: $rectorConfig->import(__DIR__ . '/rector-php81.php');
    
    // Phase 2: PHP 8.2 features (future)
    // $rectorConfig->import(__DIR__ . '/rector-php82.php');
    
    // Phase 2: PHP 8.3 features (future) 
    // $rectorConfig->import(__DIR__ . '/rector-php83.php');
    
    // Symfony compatibility rules
    // $rectorConfig->import(__DIR__ . '/rector-symfony.php');
    
    // Code quality improvements
    // $rectorConfig->import(__DIR__ . '/rector-quality.php');
    
    // Configure parallel processing
    $rectorConfig->parallel();
    
    // Cache directory
    $rectorConfig->cacheDirectory(__DIR__ . '/var/cache/rector');
};