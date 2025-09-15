<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\ParametersProcessor\Factory;

use Pgs\HashIdBundle\AnnotationProvider\AnnotationProviderInterface;
use Pgs\HashIdBundle\ParametersProcessor\ParametersProcessorInterface;

abstract class AbstractParametersProcessorFactory
{
    /**
     * @var AnnotationProviderInterface
     */
    protected $annotationProvider;

    /**
     * @var ParametersProcessorInterface
     */
    protected $noOpParametersProcessor;

    public function __construct(
        AnnotationProviderInterface $annotationProvider,
        ParametersProcessorInterface $noOpParametersProcessor,
    ) {
        $this->annotationProvider = $annotationProvider;
        $this->noOpParametersProcessor = $noOpParametersProcessor;
    }

    protected function getNoOpParametersProcessor(): ParametersProcessorInterface
    {
        return $this->noOpParametersProcessor;
    }

    protected function getAnnotationProvider(): AnnotationProviderInterface
    {
        return $this->annotationProvider;
    }
}
