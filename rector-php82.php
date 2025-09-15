<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Php82\Rector\FuncCall\Utf8DecodeEncodeToMbConvertEncodingRector;
use Rector\Php82\Rector\New_\FilesystemIteratorSkipDotsRector;
use Rector\Php82\Rector\Param\AddSensitiveParameterAttributeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNullableTypeRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;

/**
 * PHP 8.2 Features Configuration
 * 
 * Implements PHP 8.2 modernization rules for the HashId bundle.
 * Focuses on readonly classes, type improvements, and modern PHP features.
 * 
 * Applied features:
 * - Readonly classes for value objects and immutable classes
 * - Null, false, and true as standalone types  
 * - Sensitive parameter attributes for security-critical data
 * - Improved type declarations across the codebase
 */
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/tests/Fixtures',
        __DIR__ . '/tests/App',
        __DIR__ . '/var',
        __DIR__ . '/vendor',
        // Skip DemoController as it's for testing only
        __DIR__ . '/src/Controller/DemoController.php',
    ]);

    // PHP 8.2 Feature Rules
    $rectorConfig->rules([
        // Transform classes with all readonly properties to readonly classes
        ReadOnlyClassRector::class,
        
        // Add SensitiveParameter attribute to security-critical parameters
        AddSensitiveParameterAttributeRector::class,
        
        // Convert utf8_decode/encode to mb_convert_encoding
        Utf8DecodeEncodeToMbConvertEncodingRector::class,
        
        // Use FilesystemIterator::SKIP_DOTS by default
        FilesystemIteratorSkipDotsRector::class,
    ]);

    // Additional type improvement rules that complement PHP 8.2
    $rectorConfig->rules([
        // Add nullable return types based on actual returns
        ReturnNullableTypeRector::class,
        
        // Add typed properties from strict constructors
        TypedPropertyFromStrictConstructorRector::class,
        
        // Clean up unused private properties
        RemoveUnusedPrivatePropertyRector::class,
    ]);

    // Configure sensitive parameters for the bundle
    $rectorConfig->ruleWithConfiguration(AddSensitiveParameterAttributeRector::class, [
        // Mark salt parameters as sensitive in HashidsConverter
        'Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter' => [
            '__construct' => [0], // $salt parameter
        ],
    ]);

    // Import names for cleaner code
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
    
    // Performance settings
    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__ . '/var/cache/rector');
    
    // PHP 8.2 as minimum version
    $rectorConfig->phpVersion(\Rector\ValueObject\PhpVersion::PHP_82);
};