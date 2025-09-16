<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Pgs\HashIdBundle\Rector\ConfigurationModernizationRule;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ConfigurationModernizationRule::class);
};