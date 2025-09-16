<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\ParametersProcessor;

use Pgs\HashIdBundle\ParametersProcessor\Converter\ConverterInterface;

/**
 * Abstract parameters processor with PHP 8.3 optimizations.
 * 
 * Uses readonly properties where possible for better opcache optimization.
 * The parametersToProcess array remains mutable due to setParametersToProcess method requirements.
 * 
 * @since 4.0.0
 */
abstract class AbstractParametersProcessor implements ParametersProcessorInterface
{
    /** @var array<string> */
    protected array $parametersToProcess;

    /**
     * Constructor with readonly converter for immutability.
     *
     * @param ConverterInterface $converter The converter instance (readonly)
     * @param array<string> $parametersToProcess Parameters to process
     */
    public function __construct(
        protected readonly ConverterInterface $converter,
        array $parametersToProcess = []
    ) {
        $this->parametersToProcess = $parametersToProcess;
    }

    /**
     * @param array<string> $parametersToProcess
     */
    public function setParametersToProcess(array $parametersToProcess): ParametersProcessorInterface
    {
        $this->parametersToProcess = $parametersToProcess;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getParametersToProcess(): array
    {
        return $this->parametersToProcess;
    }

    public function getConverter(): ConverterInterface
    {
        return $this->converter;
    }

    /**
     * Process parameters with optimized loop.
     * 
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function process(array $parameters): array
    {
        // Early return if nothing to process
        if (!$this->needToProcess()) {
            return $parameters;
        }
        
        // Process only existing parameters for better performance
        foreach ($this->parametersToProcess as $parameter) {
            if (isset($parameters[$parameter])) {
                $parameters[$parameter] = $this->processValue($parameters[$parameter]);
            }
        }

        return $parameters;
    }

    /**
     * Check if processing is needed.
     * Optimized with direct empty check.
     */
    public function needToProcess(): bool
    {
        return !empty($this->parametersToProcess);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    abstract protected function processValue(mixed $value): mixed;
}
