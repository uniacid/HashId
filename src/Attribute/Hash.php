<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Attribute;

/**
 * PHP 8 Attribute for marking route parameters to be encoded/decoded.
 *
 * This is the modern replacement for the @Hash annotation.
 * Both are supported in v4.x for backward compatibility.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Hash
{
    /** @var array<string> */
    private array $parameters;

    /**
     * @param string|array<string> $parameters Parameter name(s) to be encoded/decoded
     */
    public function __construct(string|array $parameters)
    {
        $this->parameters = \is_array($parameters) ? $parameters : [$parameters];
    }

    /**
     * Get the parameters that should be encoded/decoded.
     * 
     * @return array<string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
