<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Reflection;

use Pgs\HashIdBundle\Exception\MissingClassOrMethodException;
use Pgs\HashIdBundle\Exception\HashIdException;
use ReflectionException;
use ReflectionMethod;

class ReflectionProvider
{
    public function getMethodReflectionFromClassString(string $class, string $method): ?ReflectionMethod
    {
        try {
            return new ReflectionMethod($class, $method);
        } catch (ReflectionException $e) {
            throw HashIdException::missingClassOrMethod($class, $method, $e);
        }
    }

    /**
     * @param mixed $object
     */
    public function getMethodReflectionFromObject($object, string $method): ?ReflectionMethod
    {
        try {
            return new ReflectionMethod($object, $method);
        } catch (ReflectionException $e) {
            throw HashIdException::missingClassOrMethod($class, $method, $e);
        }
    }
}
