<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php81\Rector\ClassMethod\NewInInitializerRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;

return static function (RectorConfig $rectorConfig): void {
    // Define paths to process
    $rectorConfig->paths([
        __DIR__ . '/../../src',
        __DIR__ . '/../../tests',
    ]);

    // Skip test fixtures and vendor
    $rectorConfig->skip([
        __DIR__ . '/../../tests/Fixtures',
        __DIR__ . '/../../vendor',
    ]);

    // Enable parallel processing for better performance
    $rectorConfig->parallel();

    // Add strict types declaration
    $rectorConfig->rule(DeclareStrictTypesRector::class);

    // Convert HashId annotations to attributes
    $rectorConfig->ruleWithConfiguration(AnnotationToAttributeRector::class, [
        new AnnotationToAttribute('Pgs\HashIdBundle\Annotation\Hash', 'Pgs\HashIdBundle\Attribute\Hash'),
    ]);

    // Constructor property promotion
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // Readonly properties for immutable values
    $rectorConfig->rule(ReadOnlyPropertyRector::class);

    // Add return type declarations where possible
    $rectorConfig->rule(AddReturnTypeDeclarationRector::class);

    // Type properties from assignments
    $rectorConfig->rule(TypedPropertyFromAssignsRector::class);

    // PHP 8.1 specific features
    $rectorConfig->rule(NewInInitializerRector::class);
    $rectorConfig->rule(NullToStrictStringFuncCallArgRector::class);

    // Custom HashId-specific rules
    $rectorConfig->singleton(
        \Pgs\HashIdBundle\Rector\AnnotationToAttributeRector::class,
        function () {
            return new \Pgs\HashIdBundle\Rector\AnnotationToAttributeRector();
        }
    );

    $rectorConfig->singleton(
        \Pgs\HashIdBundle\Rector\RouterDecoratorModernizationRector::class,
        function () {
            return new \Pgs\HashIdBundle\Rector\RouterDecoratorModernizationRector();
        }
    );

    $rectorConfig->singleton(
        \Pgs\HashIdBundle\Rector\ParameterProcessorModernizationRector::class,
        function () {
            return new \Pgs\HashIdBundle\Rector\ParameterProcessorModernizationRector();
        }
    );

    // Import level sets for comprehensive modernization
    $rectorConfig->sets([
        \Rector\Set\ValueObject\LevelSetList::UP_TO_PHP_81,
        \Rector\Set\ValueObject\SetList::CODE_QUALITY,
        \Rector\Set\ValueObject\SetList::DEAD_CODE,
        \Rector\Set\ValueObject\SetList::TYPE_DECLARATION,
    ]);

    // Configure import names
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);

    // Cache configuration for performance
    $rectorConfig->cacheDirectory(__DIR__ . '/../../var/cache/rector');

    // Beta testing metrics collection
    $rectorConfig->bootstrapFiles([
        __DIR__ . '/../../vendor/autoload.php',
    ]);

    // Add custom visitor for metrics collection
    $rectorConfig->singleton(
        \Pgs\HashIdBundle\Rector\Metrics\BetaMetricsCollector::class,
        function () {
            return new \Pgs\HashIdBundle\Rector\Metrics\BetaMetricsCollector();
        }
    );
};