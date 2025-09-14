<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Php81\Rector\Class_\MyCLabsClassToEnumRector;
use Rector\Php81\Rector\ClassConst\FinalizePublicClassConstantRector;
use Rector\Php81\Rector\ClassMethod\NewInInitializerRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php81\Rector\MethodCall\MyCLabsMethodCallToEnumConstRector;
use Rector\Php81\Rector\ClassMethod\AddReturnTypeDeclarationFromYieldsRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\TypeDeclaration\Rector\Union\UnionTypesRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
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
        ReadOnlyPropertyRector::class => [
            // Skip if there are mutable properties that need careful review
        ],
        
        ChangeSwitchToMatchRector::class => [
            // Skip complex switch statements that might need manual conversion
        ],
    ]);

    // PHP 8.1: Constructor Property Promotion (already available in PHP 8.0 but safe to apply)
    $rectorConfig->rule(ClassPropertyAssignToConstructorPromotionRector::class);
    
    // PHP 8.1: Readonly Properties
    $rectorConfig->rule(ReadOnlyPropertyRector::class);
    
    // PHP 8.1: Final Class Constants
    $rectorConfig->rule(FinalizePublicClassConstantRector::class);
    
    // PHP 8.1: New in Initializer
    $rectorConfig->rule(NewInInitializerRector::class);
    
    // PHP 8.1: First Class Callables
    $rectorConfig->rule(FirstClassCallableRector::class);
    
    // PHP 8.1: Null to Strict String
    $rectorConfig->rule(NullToStrictStringFuncCallArgRector::class);
    
    // PHP 8.1: Add Return Type from Yields
    $rectorConfig->rule(AddReturnTypeDeclarationFromYieldsRector::class);
    
    // PHP 8.0: Match Expressions (upgrade from switch when appropriate)
    $rectorConfig->rule(ChangeSwitchToMatchRector::class);
    
    // Union Types (PHP 8.0 feature, safe for PHP 8.1)
    $rectorConfig->rule(UnionTypesRector::class);
    
    // Code Quality: Combined Assignment (??= operator)
    $rectorConfig->rule(CombinedAssignRector::class);
    
    // Code Quality: Explicit Bool Compare
    $rectorConfig->rule(ExplicitBoolCompareRector::class);
    
    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__ . '/var/cache/rector');
};