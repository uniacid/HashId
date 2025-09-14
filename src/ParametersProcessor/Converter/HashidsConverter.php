<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\ParametersProcessor\Converter;

use Hashids\HashidsInterface;

class HashidsConverter implements ConverterInterface
{
    public function __construct(private HashidsInterface $hashids)
    {
    }

    public function encode(mixed $value): string
    {
        return $this->hashids->encode($value);
    }

    public function decode(string $value): mixed
    {
        $result = $this->hashids->decode($value);

        return $result[0] ?? $value;
    }
}
