<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Exception;

/**
 * Exception thrown when a required dependency is missing.
 *
 * @deprecated since 4.0, use HashIdException::missingDependency() instead
 */
class MissingDependencyException extends HashIdException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            HashIdError::MISSING_DEPENDENCY,
            $message,
            $previous
        );
    }
}
