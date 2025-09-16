<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Rector\Class_\InvokableControllerRector;
use Rector\Symfony\Rector\MethodCall\ContainerGetToConstructorInjectionRector;
use Rector\Symfony\Rector\ClassMethod\ResponseReturnTypeControllerActionRector;
use Rector\Symfony\Rector\ClassMethod\ParamConverterAttributeToMapEntityAttributeRector;

return static function (RectorConfig $rectorConfig): void {
    // Import PHP 8.3 configuration as base
    $rectorConfig->import(__DIR__ . '/rector-php83-beta.php');

    // Symfony 6.4 specific modernizations

    // Convert to invokable controllers where appropriate
    $rectorConfig->rule(InvokableControllerRector::class);

    // Constructor injection instead of container get
    $rectorConfig->rule(ContainerGetToConstructorInjectionRector::class);

    // Add Response return types to controller actions
    $rectorConfig->rule(ResponseReturnTypeControllerActionRector::class);

    // Convert ParamConverter to MapEntity attribute
    $rectorConfig->rule(ParamConverterAttributeToMapEntityAttributeRector::class);

    // Import Symfony sets
    $rectorConfig->sets([
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_64,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_CODE_QUALITY,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
        \Rector\Symfony\Set\SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);

    // Custom HashId Symfony integration rules
    $rectorConfig->singleton(
        \Pgs\HashIdBundle\Rector\SymfonyRouteAttributeIntegrationRector::class,
        function () {
            return new \Pgs\HashIdBundle\Rector\SymfonyRouteAttributeIntegrationRector();
        }
    );

    $rectorConfig->singleton(
        \Pgs\HashIdBundle\Rector\DoctrineParamConverterCompatibilityRector::class,
        function () {
            return new \Pgs\HashIdBundle\Rector\DoctrineParamConverterCompatibilityRector();
        }
    );

    $rectorConfig->singleton(
        \Pgs\HashIdBundle\Rector\TwigExtensionModernizationRector::class,
        function () {
            return new \Pgs\HashIdBundle\Rector\TwigExtensionModernizationRector();
        }
    );

    // Configure Symfony container path
    $rectorConfig->symfonyContainerXml(__DIR__ . '/../../var/cache/dev/App_KernelDevDebugContainer.xml');

    // Auto-import Symfony classes
    $rectorConfig->autoloadPaths([
        __DIR__ . '/../../vendor/symfony',
    ]);
};