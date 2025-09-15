<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\ParametersProcessor\Factory;

use Pgs\HashIdBundle\Annotation\Hash;
use Pgs\HashIdBundle\AnnotationProvider\AnnotationProviderInterface;
use Pgs\HashIdBundle\Exception\InvalidControllerException;
use Pgs\HashIdBundle\Exception\MissingClassOrMethodException;
use Pgs\HashIdBundle\ParametersProcessor\ParametersProcessorInterface;

class DecodeParametersProcessorFactory extends AbstractParametersProcessorFactory
{
    /**
     * @var ParametersProcessorInterface
     */
    protected $decodeParametersProcessor;

    public function __construct(
        AnnotationProviderInterface $annotationProvider,
        ParametersProcessorInterface $noOpParametersProcessor,
        ParametersProcessorInterface $decodeParametersProcessor,
    ) {
        parent::__construct($annotationProvider, $noOpParametersProcessor);
        $this->decodeParametersProcessor = $decodeParametersProcessor;
    }

    protected function getDecodeParametersProcessor(): ParametersProcessorInterface
    {
        return $this->decodeParametersProcessor;
    }

    /**
     * @param object $controller
     */
    public function createControllerDecodeParametersProcessor($controller, string $method): ParametersProcessorInterface
    {
        try {
            /** @var Hash $annotation */
            $annotation = $this->getAnnotationProvider()->getFromObject(
                $controller,
                $method,
                Hash::class,
            );
        } catch (InvalidControllerException | MissingClassOrMethodException $e) {
            $annotation = null;
        }

        return null !== $annotation
            ? $this->getDecodeParametersProcessor()->setParametersToProcess($annotation->getParameters())
            : $this->getNoOpParametersProcessor();
    }
}
