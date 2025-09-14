<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\ParametersProcessor;

class Decode extends AbstractParametersProcessor
{
    /**
     * @param mixed $value
     * @return mixed
     */
    protected function processValue(mixed $value): mixed
    {
        return $this->getConverter()->decode($value);
    }
}
