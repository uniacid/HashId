<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\DeadCode\Rector\Array_\RemoveUnusedNonEmptyArrayBeforeForeachRector;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\ClassConst\RemoveUnusedPrivateClassConstantRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector;
use Rector\DeadCode\Rector\ConstFetch\RemovePhpVersionIdCheckRector;
use Rector\DeadCode\Rector\For_\RemoveDeadContinueRector;
use Rector\DeadCode\Rector\For_\RemoveDeadIfForeachForRector;
use Rector\DeadCode\Rector\For_\RemoveDeadLoopRector;
use Rector\DeadCode\Rector\Foreach_\RemoveUnusedForeachKeyRector;
use Rector\DeadCode\Rector\FunctionLike\RemoveDeadReturnRector;
use Rector\DeadCode\Rector\If_\RemoveDeadInstanceOfRector;
use Rector\DeadCode\Rector\If_\SimplifyIfElseWithSameContentRector;
use Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Rector\DeadCode\Rector\StmtsAwareInterface\RemoveJustPropertyFetchRector;
use Rector\DeadCode\Rector\Switch_\RemoveDuplicatedCaseInSwitchRector;
use Rector\DeadCode\Rector\Ternary\TernaryToBooleanOrFalseToBooleanAndRector;
use Rector\DeadCode\Rector\TryCatch\RemoveDeadTryCatchRector;
use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\CodeQuality\Rector\BooleanAnd\SimplifyEmptyArrayCheckRector;
use Rector\CodeQuality\Rector\Expression\InlineIfToExplicitIfRector;
use Rector\CodeQuality\Rector\For_\ForRepeatedCountToOwnVariableRector;
use Rector\CodeQuality\Rector\Foreach_\ForeachToInArrayRector;
use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\CodeQuality\Rector\FuncCall\UnwrapSprintfOneArgumentRector;
use Rector\CodeQuality\Rector\If_\ConsecutiveNullCompareReturnsToNullCoalesceQueueRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector;
use Rector\CodeQuality\Rector\Include_\AbsolutizeRequireAndIncludePathRector;
use Rector\CodeQuality\Rector\LogicalAnd\LogicalToBooleanRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/tests/Fixtures',
        __DIR__ . '/var',
        __DIR__ . '/vendor',
        
        // Skip rules that might be too aggressive for existing codebase
        InlineIfToExplicitIfRector::class => [
            // Keep some ternary expressions for readability
        ],
        
        SimplifyIfElseToTernaryRector::class => [
            // Avoid converting complex if-else to ternary
        ],
        
        // Skip removing unused parameters in interfaces/abstract methods
        RemoveUnusedPrivateMethodRector::class => [
            // Keep interface compliance methods
        ],
    ]);

    // Apply comprehensive dead code removal
    $rectorConfig->sets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
    ]);

    // Specific dead code removal rules
    $rectorConfig->rules([
        RemoveUnusedVariableAssignRector::class,
        RemoveUnusedPrivatePropertyRector::class,
        RemoveUnusedPrivateClassConstantRector::class,
        RemoveUnusedPrivateMethodRector::class,
        RemoveUselessReturnTagRector::class,
        RemoveDeadReturnRector::class,
        RemoveDeadInstanceOfRector::class,
        RemoveDeadTryCatchRector::class,
        RemoveDeadLoopRector::class,
        RemoveDeadContinueRector::class,
        RemoveDeadIfForeachForRector::class,
        RemoveUnusedNonEmptyArrayBeforeForeachRector::class,
        RemoveUnusedForeachKeyRector::class,
        RemoveDuplicatedCaseInSwitchRector::class,
        RemoveAndTrueRector::class,
        RecastingRemovalRector::class,
        RemoveConcatAutocastRector::class,
        RemovePhpVersionIdCheckRector::class,
        RemoveParentCallWithoutParentRector::class,
        RemoveJustPropertyFetchRector::class,
        RemoveNonExistingVarAnnotationRector::class,
        TernaryToBooleanOrFalseToBooleanAndRector::class,
        SimplifyIfElseWithSameContentRector::class,
    ]);

    // Code quality improvements
    $rectorConfig->rules([
        CallableThisArrayToAnonymousFunctionRector::class,
        SimplifyEmptyArrayCheckRector::class,
        ForRepeatedCountToOwnVariableRector::class,
        ForeachToInArrayRector::class,
        CompactToVariablesRector::class,
        UnwrapSprintfOneArgumentRector::class,
        ConsecutiveNullCompareReturnsToNullCoalesceQueueRector::class,
        ExplicitBoolCompareRector::class,
        ShortenElseIfRector::class,
        SimplifyIfReturnBoolRector::class,
        SimplifyBoolIdenticalTrueRector::class,
        AbsolutizeRequireAndIncludePathRector::class,
        LogicalToBooleanRector::class,
    ]);

    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__ . '/var/cache/rector');
};