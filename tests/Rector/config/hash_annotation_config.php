<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Pgs\HashIdBundle\Rector\HashAnnotationToAttributeRule;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(HashAnnotationToAttributeRule::class);
};