<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\AnnotationProvider;

interface AnnotationProviderInterface
{
    /**
     * @template T of object
     *
     * @param class-string<T> $annotationClassName
     *
     * @return T|null
     */
    public function getFromString(string $controller, string $annotationClassName): ?object;

    /**
     * @template T of object
     *
     * @param object $controller
     * @param class-string<T> $annotationClassName
     *
     * @return T|null
     */
    public function getFromObject($controller, string $method, string $annotationClassName): ?object;
}
