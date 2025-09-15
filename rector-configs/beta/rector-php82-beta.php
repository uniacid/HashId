<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Php82\Rector\FuncCall\Utf8DecodeEncodeToMbConvertEncodingRector;
use Rector\Php82\Rector\New_\FilesystemIteratorSkipDotsRector;
use Rector\Php82\Rector\Param\AddSensitiveParameterAttributeRector;

return static function (RectorConfig $rectorConfig): void {
    // Import PHP 8.1 configuration as base
    $rectorConfig->import(__DIR__ . '/rector-php81-beta.php');

    // PHP 8.2 specific features

    // Readonly classes for DTOs and value objects
    $rectorConfig->rule(ReadOnlyClassRector::class);

    // DNF Types (Disjunctive Normal Form)
    // This is automatically handled by type declaration rules

    // Sensitive parameter attribute for security
    $rectorConfig->rule(AddSensitiveParameterAttributeRector::class);

    // UTF-8 handling improvements
    $rectorConfig->rule(Utf8DecodeEncodeToMbConvertEncodingRector::class);

    // Filesystem iterator improvements
    $rectorConfig->rule(FilesystemIteratorSkipDotsRector::class);

    // Import PHP 8.2 level set
    $rectorConfig->sets([
        \Rector\Set\ValueObject\LevelSetList::UP_TO_PHP_82,
    ]);

    // Custom rule for HashId configuration DTOs
    $rectorConfig->singleton(
        \Pgs\HashIdBundle\Rector\ConfigurationDTOReadonlyRector::class,
        function () {
            return new \Pgs\HashIdBundle\Rector\ConfigurationDTOReadonlyRector();
        }
    );

    // Custom rule for sensitive parameter marking
    $rectorConfig->singleton(
        \Pgs\HashIdBundle\Rector\SaltParameterSensitiveRector::class,
        function () {
            return new \Pgs\HashIdBundle\Rector\SaltParameterSensitiveRector();
        }
    );
};