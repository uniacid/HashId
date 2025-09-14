<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
readonly class Hash
{
    private array $parameters;

    public function __construct(array $parameters)
    {
        if (isset($parameters['value']) && \is_array($parameters['value'])) {
            $parameters = $parameters['value'];
        }

        $this->parameters = $parameters;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
