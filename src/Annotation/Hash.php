<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Annotation;

/**
 * @Annotation
 *
 * @Target({"METHOD"})
 */
readonly class Hash
{
    /** @var array<int, string> */
    private array $parameters;

    /** @param array<string, mixed>|array<int, string> $parameters */
    public function __construct(array $parameters)
    {
        if (isset($parameters['value']) && \is_array($parameters['value'])) {
            $parameters = $parameters['value'];
        }

        $this->parameters = $parameters;
    }

    /** @return array<int, string> */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
