<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;
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

    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__ . '/var/cache/rector');
};