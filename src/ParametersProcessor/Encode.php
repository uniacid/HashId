<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\ParametersProcessor;

class Encode extends AbstractParametersProcessor
{
    protected function processValue($value): string
    {
        return $this->getConverter()->encode($value);
    }
}
