<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\ParametersProcessor;

/**
 * Parameter processor for decoding hash strings to integer values.
 *
 * This processor transforms obfuscated hash strings back into their original
 * integer values using the configured Hashids converter. It's used during
 * request processing to decode URL parameters before they reach the controller.
 *
 * @package Pgs\HashIdBundle\ParametersProcessor
 * @since 3.0.0
 *
 * @example
 * ```php
 * // Configuration
 * $processor = new Decode($converter);
 * $processor->setParametersToProcess(['id', 'userId']);
 *
 * // Decoding parameters from request
 * $decoded = $processor->process([
 *     'id' => 'x7Va91',
 *     'userId' => 'k9Zq42',
 *     'name' => 'John'
 * ]);
 * // Result: ['id' => 123, 'userId' => 456, 'name' => 'John']
 * ```
 */
class Decode extends AbstractParametersProcessor
{
    /**
     * Processes a single value by decoding it from hash to integer.
     *
     * Converts hash strings back to their original integer values using the
     * configured Hashids converter. Non-hash values are returned unchanged.
     *
     * @param mixed $value The value to decode (typically a hash string)
     *
     * @return mixed The decoded integer if input was a valid hash, original value otherwise
     *
     * @example
     * ```php
     * $decoded = $this->processValue('x7Va91');  // Returns: 123
     * $decoded = $this->processValue('invalid'); // Returns: 'invalid' (unchanged)
     * $decoded = $this->processValue(456);       // Returns: 456 (unchanged)
     * ```
     */
    protected function processValue(mixed $value): mixed
    {
        return $this->getConverter()->decode($value);
    }
}
