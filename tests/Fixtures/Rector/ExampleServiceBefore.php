<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Fixtures\Rector;

/**
 * Example service class to test constructor property promotion
 */
class ExampleServiceBefore
{
    private string $name;
    private int $value;
    private ?array $options;

    public function __construct(string $name, int $value, ?array $options = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->options = $options;
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
     * Example switch statement that could be converted to match expression
     */
    public function getStatusMessage(string $status): string
    {
        switch ($status) {
            case 'pending':
                return 'Processing your request';
            case 'completed':
                return 'Request completed successfully';
            case 'failed':
                return 'Request failed';
            default:
                return 'Unknown status';
        }
    }
}