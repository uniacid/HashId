<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;
use Rector\Symfony\Set\SymfonySetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/v3-example',
    ]);

    // Define custom rules for HashId Bundle migration
    $rectorConfig->ruleWithConfiguration(AnnotationToAttributeRector::class, [
        new AnnotationToAttribute('Pgs\HashIdBundle\Annotation\Hash', 'Pgs\HashIdBundle\Attribute\Hash'),
        new AnnotationToAttribute('Symfony\Component\Routing\Annotation\Route', 'Symfony\Component\Routing\Attribute\Route'),
    ]);

    // Import Symfony upgrade sets
    $rectorConfig->sets([
        SymfonySetList::SYMFONY_64,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);

    // Add PHP 8 features
    $rectorConfig->rule(AddVoidReturnTypeWhereNoReturnRector::class);

    // PHP version to target
    $rectorConfig->phpVersion(80100);

    // Import names
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);

    // Skip certain files or directories if needed
    $rectorConfig->skip([
        __DIR__ . '/v4-example',  // Don't process already migrated code
    ]);
};