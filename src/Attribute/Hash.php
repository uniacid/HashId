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
    /**
     * Maximum number of parameters allowed.
     */
    private const MAX_PARAMETERS = 20;

    /**
     * Pattern for valid parameter names.
     */
    private const PARAM_NAME_PATTERN = '/^[a-zA-Z0-9_]+$/';

    /** @var array<string> */
    private array $parameters;

    /**
     * @param string|array<string> $parameters Parameter name(s) to be encoded/decoded
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public function __construct(string|array $parameters)
    {
        $this->parameters = \is_array($parameters) ? $parameters : [$parameters];

        // Validate parameter count
        if (\count($this->parameters) > self::MAX_PARAMETERS) {
            throw new \InvalidArgumentException(\sprintf(
                'Too many parameters specified (max %d, got %d)',
                self::MAX_PARAMETERS,
                \count($this->parameters)
            ));
        }

        // Validate parameter names
        foreach ($this->parameters as $param) {
            if (!\preg_match(self::PARAM_NAME_PATTERN, $param)) {
                throw new \InvalidArgumentException(\sprintf(
                    'Invalid parameter name: "%s". Parameter names must contain only letters, numbers, and underscores.',
                    $param
                ));
            }

            // Additional length check for parameter names
            if (\strlen($param) > 100) {
                throw new \InvalidArgumentException(\sprintf(
                    'Parameter name too long: "%s" (max 100 characters)',
                    $param
                ));
            }
        }
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
