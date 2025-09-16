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
        // Skip Rector fixtures
        __DIR__ . '/tests/Rector/Fixtures',
        // Skip cache directories
        __DIR__ . '/var',
        __DIR__ . '/tests/App/cache',
    ]);

    // Import modular configurations based on migration stage
    // ======================================================

    // Stage 1: PHP 8.1 features (ACTIVE)
    // Achieves ~30% automation on its own
    $rectorConfig->import(__DIR__ . '/rector-php81.php');

    // Stage 2: PHP 8.2 features
    // Uncomment when ready for next stage (~20% additional automation)
    // $rectorConfig->import(__DIR__ . '/rector-php82.php');

    // Stage 3: PHP 8.3 features
    // Uncomment for latest PHP features (~15% additional automation)
    // $rectorConfig->import(__DIR__ . '/rector-php83.php');

    // Stage 4: Symfony modernization
    // Uncomment for Symfony 6.4/7.0 compatibility (~25% additional automation)
    // $rectorConfig->import(__DIR__ . '/rector-symfony.php');

    // Stage 5: Code quality improvements
    // Uncomment for final polish (~10% additional automation)
    // $rectorConfig->import(__DIR__ . '/rector-quality.php');

    // Optional: Compatibility mode for gradual migration
    // Keeps both annotations and attributes during transition
    // $rectorConfig->import(__DIR__ . '/rector-compatibility.php');

    // Register custom HashId-specific rules
    // These are always active as they're specific to this bundle
    $customRulesDir = __DIR__ . '/rector-rules';
    if (is_dir($customRulesDir)) {
        // Auto-discover custom rules
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($customRulesDir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && str_contains($file->getBasename(), 'Rule')) {
                $relativePath = str_replace($customRulesDir . '/', '', $file->getPathname());
                $className = 'Pgs\\HashIdBundle\\Rector\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);

                if (class_exists($className)) {
                    $rectorConfig->rule($className);
                }
            }
        }
    }

    // Performance optimizations
    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__ . '/var/cache/rector');

    // Import management
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();
    $rectorConfig->removeUnusedImports();
};