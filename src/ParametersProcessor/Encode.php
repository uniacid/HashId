<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\ParametersProcessor;

/**
 * Parameter processor for encoding integer values to hash strings.
 *
 * This processor transforms integer route parameters into obfuscated hash
 * strings using the configured Hashids converter. It's used during URL
 * generation to create non-predictable URLs from integer IDs.
 *
 * @package Pgs\HashIdBundle\ParametersProcessor
 * @since 3.0.0
 *
 * @example
 * ```php
 * // Configuration
 * $processor = new Encode($converter);
 * $processor->setParametersToProcess(['id', 'userId']);
 *
 * // Encoding parameters
 * $encoded = $processor->process([
 *     'id' => 123,
 *     'userId' => 456,
 *     'name' => 'John'
 * ]);
 * // Result: ['id' => 'x7Va91', 'userId' => 'k9Zq42', 'name' => 'John']
 * ```
 */
class Encode extends AbstractParametersProcessor
{
    /**
     * Processes a single value by encoding it to a hash string.
     *
     * Converts integer values to their hash representation using the
     * configured Hashids converter. Non-integer values are returned unchanged.
     *
     * @param mixed $value The value to encode (typically an integer)
     *
     * @return mixed The encoded hash string if input was an integer, original value otherwise
     *
     * @example
     * ```php
     * $encoded = $this->processValue(123);  // Returns: 'x7Va91'
     * $encoded = $this->processValue('abc'); // Returns: 'abc' (unchanged)
     * ```
     */
    protected function processValue(mixed $value): mixed
    {
        return $this->getConverter()->encode($value);
    }
}
