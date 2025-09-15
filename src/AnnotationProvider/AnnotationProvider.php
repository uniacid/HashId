<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\AnnotationProvider;

use Doctrine\Common\Annotations\Reader;
use Pgs\HashIdBundle\Exception\InvalidControllerException;
use Pgs\HashIdBundle\Exception\MissingClassOrMethodException;
use Pgs\HashIdBundle\Reflection\ReflectionProvider;

class AnnotationProvider implements AnnotationProviderInterface
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var ReflectionProvider
     */
    protected $reflectionProvider;

    public function __construct(Reader $reader, ReflectionProvider $reflectionProvider)
    {
        $this->reader = $reader;
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $annotationClassName
     *
     * @return T|null
     */
    public function getFromString(string $controller, string $annotationClassName): ?object
    {
        $explodedControllerString = \explode('::', $controller);
        if (2 !== \count($explodedControllerString)) {
            $message = \sprintf('The "%s" controller is not a valid "class::method" string.', $controller);

            throw new InvalidControllerException($message);
        }
        $reflection = $this->reflectionProvider->getMethodReflectionFromClassString(...$explodedControllerString);

        if ($reflection === null) {
            return null;
        }

        /** @var T|null */
        return $this->reader->getMethodAnnotation($reflection, $annotationClassName);
    }

    /**
     * @template T of object
     *
     * @param object $controller
     * @param class-string<T> $annotationClassName
     *
     * @throws InvalidControllerException
     * @throws MissingClassOrMethodException
     *
     * @return T|null
     */
    public function getFromObject($controller, string $method, string $annotationClassName): ?object
    {
        if (!\is_object($controller)) {
            throw new InvalidControllerException('Provided controller is not an object');
        }
        $reflection = $this->reflectionProvider->getMethodReflectionFromObject($controller, $method);

        if ($reflection === null) {
            return null;
        }

        /** @var T|null */
        return $this->reader->getMethodAnnotation($reflection, $annotationClassName);
    }
}
