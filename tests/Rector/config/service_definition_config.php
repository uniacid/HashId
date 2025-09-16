<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Pgs\HashIdBundle\Rector\ServiceDefinitionRule;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ServiceDefinitionRule::class);
};