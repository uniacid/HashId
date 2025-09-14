<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Fixtures\Rector;

/**
 * Example service class after Rector PHP 8.1 transformations
 * This shows the expected result after applying constructor property promotion and match expressions.
 */
class ExampleServiceAfter
{
    public function __construct(
        private string $name,
        private int $value,
        private ?array $options = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * Example match expression (converted from switch statement).
     */
    public function getStatusMessage(string $status): string
    {
        return match ($status) {
            'pending' => 'Processing your request',
            'completed' => 'Request completed successfully',
            'failed' => 'Request failed',
            default => 'Unknown status',
        };
    }
}
