<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle;

use Pgs\HashIdBundle\DependencyInjection\Compiler\EventSubscriberCompilerPass;
use Pgs\HashIdBundle\DependencyInjection\Compiler\HasherRegistryCompilerPass;
use Pgs\HashIdBundle\DependencyInjection\Compiler\HashidsConverterCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * HashId Bundle for Symfony - Automatic route parameter obfuscation.
 *
 * This bundle provides automatic encoding/decoding of integer route parameters
 * using the Hashids library. It transforms predictable URLs like `/order/123`
 * into obfuscated URLs like `/order/4w9aA11avM` while maintaining transparent
 * usage in controllers and Twig templates.
 *
 * Key features:
 * - Transparent integration with Symfony routing
 * - Annotation/Attribute-driven configuration
 * - Backward compatibility with v3.x (annotations) and v4.x (attributes)
 * - Support for multiple hashing strategies
 * - Compatible with Symfony ParamConverter
 *
 * @package Pgs\HashIdBundle
 * @since 3.0.0
 *
 * @example Basic usage with PHP 8 attributes:
 * ```php
 * use Pgs\HashIdBundle\Attribute\Hash;
 *
 * class OrderController {
 *     #[Hash('id')]
 *     public function show(int $id): Response {
 *         // $id is automatically decoded from hash to integer
 *     }
 * }
 * ```
 *
 * @see https://github.com/uniacid/HashId Documentation and migration guide
 */
class PgsHashIdBundle extends Bundle
{
    /**
     * Builds the bundle by registering compiler passes.
     *
     * Registers the necessary compiler passes for:
     * - Hashids converter configuration
     * - Event subscriber registration for parameter processing
     *
     * @param ContainerBuilder $container The Symfony container builder
     *
     * @return void
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new HasherRegistryCompilerPass());
        $container->addCompilerPass(new HashidsConverterCompilerPass());
        $container->addCompilerPass(new EventSubscriberCompilerPass());
    }
}
