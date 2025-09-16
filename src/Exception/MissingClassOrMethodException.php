<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Exception;

/**
 * Exception thrown when a class or method is missing.
 * 
 * @deprecated since 4.0, use HashIdException::missingClassOrMethod() instead
 */
class MissingClassOrMethodException extends HashIdException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            HashIdError::MISSING_CLASS_OR_METHOD,
            $message,
            $previous
        );
    }
}
