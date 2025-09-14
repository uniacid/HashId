<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Rector\Class_\CommandPropertyToAttributeRector;
use Rector\Symfony\Rector\ClassMethod\ResponseReturnTypeControllerActionRector;
use Rector\Symfony\Rector\ClassMethod\TemplateAnnotationToThisRenderRector;
use Rector\Symfony\Rector\Closure\ContainerGetNameToTypeInClosureRector;
use Rector\Symfony\Rector\MethodCall\ContainerGetToConstructorInjectionRector;
use Rector\Symfony\Rector\StaticCall\ParseFileRector;
use Rector\Symfony\Rector\BinaryOp\ResponseStatusCodeRector;
use Rector\Doctrine\Set\DoctrineSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/tests/Fixtures',
        __DIR__ . '/var',
        __DIR__ . '/vendor',
        
        // Skip specific Symfony rules that might need manual attention
        ContainerGetToConstructorInjectionRector::class => [
            // Skip if there are complex service dependencies that need manual review
        ],
        
        // Skip template-related rules as this is a bundle, not full app
        TemplateAnnotationToThisRenderRector::class,
    ]);

    // Symfony 6.4 LTS compatibility
    $rectorConfig->sets([
        SymfonySetList::SYMFONY_64,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ]);
    
    // Doctrine compatibility (since bundle uses annotations)
    $rectorConfig->sets([
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
    ]);

    // Specific Symfony improvements
    $rectorConfig->rule(ResponseReturnTypeControllerActionRector::class);
    $rectorConfig->rule(ContainerGetNameToTypeInClosureRector::class);
    $rectorConfig->rule(ParseFileRector::class);
    $rectorConfig->rule(ResponseStatusCodeRector::class);
    
    // Command improvements (if any console commands exist)
    $rectorConfig->rule(CommandPropertyToAttributeRector::class);

    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__ . '/var/cache/rector');
};