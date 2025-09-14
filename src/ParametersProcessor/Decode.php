<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\ParametersProcessor;

class Decode extends AbstractParametersProcessor
{
    protected function processValue($value)
    {
        return $this->getConverter()->decode($value);
    }
}
