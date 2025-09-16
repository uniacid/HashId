<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Exception;

/**
 * Exception thrown when an invalid controller is provided.
 *
 * @deprecated since 4.0, use HashIdException::invalidController() instead
 */
class InvalidControllerException extends HashIdException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            HashIdError::INVALID_CONTROLLER,
            $message,
            $previous
        );
    }
}
