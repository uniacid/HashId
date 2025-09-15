<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\ParametersProcessor;

use Pgs\HashIdBundle\ParametersProcessor\Converter\ConverterInterface;

abstract class AbstractParametersProcessor implements ParametersProcessorInterface
{
    /** @var array<string> */
    protected array $parametersToProcess = [];

    protected ConverterInterface $converter;

    /**
     * @param array<string> $parametersToProcess
     */
    public function __construct(ConverterInterface $converter, array $parametersToProcess = [])
    {
        $this->converter = $converter;
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
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function process(array $parameters): array
    {
        foreach ($this->getParametersToProcess() as $parameter) {
            if (isset($parameters[$parameter])) {
                $parameters[$parameter] = $this->processValue($parameters[$parameter]);
            }
        }

        return $parameters;
    }

    public function needToProcess(): bool
    {
        return \count($this->getParametersToProcess()) > 0;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    abstract protected function processValue(mixed $value): mixed;
}
