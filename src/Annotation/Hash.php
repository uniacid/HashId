<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Annotation;

/**
 * Legacy annotation for marking route parameters to be hashed.
 *
 * @deprecated since 4.0.0, use Pgs\HashIdBundle\Attribute\Hash instead
 *
 * This annotation is maintained for backward compatibility with v3.x projects.
 * New projects should use the PHP 8 attribute version. This class will be
 * removed in v5.0.
 *
 * Migration example:
 * ```php
 * // Old (v3.x):
 * use Pgs\HashIdBundle\Annotation\Hash;
 * ⁣@Hash("id")
 *
 * // New (v4.x):
 * use Pgs\HashIdBundle\Attribute\Hash;
 * #[Hash('id')]
 * ```
 *
 * @Annotation
 * @Target({"METHOD"})
 *
 * @package Pgs\HashIdBundle\Annotation
 * @since 3.0.0
 * @see \Pgs\HashIdBundle\Attribute\Hash The modern attribute replacement
 */
readonly class Hash
{
    /** @var array<int, string> */
    public readonly array $parameters;

    /** @var string The hasher to use for encoding/decoding */
    public readonly string $hasher;

    /** @param array<string, mixed>|array<int, string> $parameters */
    public function __construct(array $parameters)
    {
        // Handle annotation format
        if (isset($parameters['value'])) {
            if (\is_array($parameters['value'])) {
                $this->parameters = array_values($parameters['value']);
            } else {
                $this->parameters = [$parameters['value']];
            }
            $hasher = isset($parameters['hasher']) ? (string) $parameters['hasher'] : 'default';
        } else {
            // Handle direct array format
            $this->parameters = array_values(array_filter($parameters, 'is_string'));
            $hasher = 'default';
        }

        // Normalize hasher name
        $hasher = trim($hasher);
        if (empty($hasher)) {
            $hasher = 'default';
        }
        $this->hasher = $hasher;
    }

    /** @return array<int, string> */
    public function getParameters(): array
    {
        return $this->parameters;
    }
    
    /** @return string */
    public function getHasher(): string
    {
        return $this->hasher;
    }
}
