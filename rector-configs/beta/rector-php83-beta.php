<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassConst\AddTypeToConstRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Php83\Rector\FuncCall\JsonEncodeToJsonValidateRector;

return static function (RectorConfig $rectorConfig): void {
    // Import PHP 8.2 configuration as base
    $rectorConfig->import(__DIR__ . '/rector-php82-beta.php');

    // PHP 8.3 specific features

    // Typed class constants
    $rectorConfig->rule(AddTypeToConstRector::class);

    // Override attribute for clarity
    $rectorConfig->rule(AddOverrideAttributeToOverriddenMethodsRector::class);

    // JSON validation improvements
    $rectorConfig->rule(JsonEncodeToJsonValidateRector::class);

    // Import PHP 8.3 level set
    $rectorConfig->sets([
        \Rector\Set\ValueObject\LevelSetList::UP_TO_PHP_83,
    ]);

    // Custom rule for HashId constants typing
    $rectorConfig->singleton(
        \Pgs\HashIdBundle\Rector\TypedConfigurationConstantsRector::class,
        function () {
            return new \Pgs\HashIdBundle\Rector\TypedConfigurationConstantsRector();
        }
    );

    // Custom rule for JSON validation in API responses
    $rectorConfig->singleton(
        \Pgs\HashIdBundle\Rector\JsonResponseValidationRector::class,
        function () {
            return new \Pgs\HashIdBundle\Rector\JsonResponseValidationRector();
        }
    );

    // Custom rule for dynamic property deprecation handling
    $rectorConfig->singleton(
        \Pgs\HashIdBundle\Rector\DynamicPropertyToAttributeRector::class,
        function () {
            return new \Pgs\HashIdBundle\Rector\DynamicPropertyToAttributeRector();
        }
    );
};