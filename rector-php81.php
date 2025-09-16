<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Php81\Rector\ClassConst\FinalizePublicClassConstantRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\CodeQuality\Rector\Assign\CombinedAssignRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->skip([
        // Skip test fixtures and generated files
        __DIR__ . '/tests/Fixtures',
        __DIR__ . '/tests/Rector/Fixtures',
        __DIR__ . '/var',
        __DIR__ . '/vendor',
        __DIR__ . '/tests/App/cache',

        // Skip specific patterns that might need manual attention
        ChangeSwitchToMatchRector::class => [
            // Skip complex switch statements that might need manual review
            __DIR__ . '/src/Service/RouterDecorator.php',
        ],
        ReadOnlyPropertyRector::class => [
            // Skip entities that might have Doctrine implications
            __DIR__ . '/tests/App/Entity',
        ],
    ]);

    // PHP 8.0 Features
    $rectorConfig->rule(ClassPropertyAssignToConstructorPromotionRector::class);
    $rectorConfig->rule(ChangeSwitchToMatchRector::class);

    // PHP 8.1 Features
    $rectorConfig->rule(ReadOnlyPropertyRector::class);
    $rectorConfig->rule(FirstClassCallableRector::class);
    $rectorConfig->rule(FinalizePublicClassConstantRector::class);

    // Type Declaration Improvements
    $rectorConfig->rule(TypedPropertyFromAssignsRector::class);
    $rectorConfig->rule(ReturnTypeFromReturnNewRector::class);

    // Code Quality
    $rectorConfig->rule(CombinedAssignRector::class);

    // Custom HashId-specific rules
    $customRulesDir = __DIR__ . '/rector-rules';
    if (is_dir($customRulesDir)) {
        // Hash annotation to attribute conversion
        $hashRuleClass = 'Pgs\\HashIdBundle\\Rector\\HashAnnotationToAttributeRule';
        if (class_exists($hashRuleClass)) {
            $rectorConfig->rule($hashRuleClass);
        }
    }

    // Performance optimizations
    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__ . '/var/cache/rector');
    $rectorConfig->importNames();
    $rectorConfig->removeUnusedImports();
};