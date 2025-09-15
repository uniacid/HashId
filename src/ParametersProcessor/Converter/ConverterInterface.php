<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\ParametersProcessor\Converter;

interface ConverterInterface
{
    /**
     * Encode a value to a hashed string.
     *
     * @param mixed $value The value to encode (typically an integer or array of integers)
     * @return string The encoded hash string
     */
    public function encode(mixed $value): string;

    /**
     * Decode a hashed string back to its original value.
     *
     * @param string $value The hash string to decode
     * @return mixed The decoded value (typically an integer or array of integers)
     */
    public function decode(string $value): mixed;
}
