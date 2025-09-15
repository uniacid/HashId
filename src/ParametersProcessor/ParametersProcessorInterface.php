<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\ParametersProcessor;

/**
 * Interface for parameter processors that encode/decode route parameters.
 *
 * Implementations of this interface handle the transformation of route
 * parameters between their integer and hash representations. This is
 * the core abstraction for the encoding/decoding logic in the HashId bundle.
 *
 * @package Pgs\HashIdBundle\ParametersProcessor
 * @since 3.0.0
 */
interface ParametersProcessorInterface
{
    /**
     * Processes parameters by encoding or decoding them.
     *
     * Transforms the specified parameters according to the implementation
     * (encoding integers to hashes or decoding hashes to integers).
     *
     * @param array<string, mixed> $parameters The parameters to process
     *
     * @return array<string, mixed> The processed parameters
     *
     * @example
     * ```php
     * // Encoding example
     * $encoder->process(['id' => 123, 'name' => 'John']);
     * // Returns: ['id' => 'abc123', 'name' => 'John']
     *
     * // Decoding example
     * $decoder->process(['id' => 'abc123', 'name' => 'John']);
     * // Returns: ['id' => 123, 'name' => 'John']
     * ```
     */
    public function process(array $parameters): array;

    /**
     * Sets the list of parameters that should be processed.
     *
     * Defines which parameter names should be transformed when process() is called.
     * Parameters not in this list will be passed through unchanged.
     *
     * @param array<int, string> $parametersToProcess List of parameter names to process
     *
     * @return self Returns self for method chaining
     */
    public function setParametersToProcess(array $parametersToProcess): self;

    /**
     * Gets the list of parameters configured for processing.
     *
     * @return array<int, string> List of parameter names that will be processed
     */
    public function getParametersToProcess(): array;

    /**
     * Checks if this processor has parameters to process.
     *
     * @return bool True if there are parameters configured for processing, false otherwise
     */
    public function needToProcess(): bool;
}
