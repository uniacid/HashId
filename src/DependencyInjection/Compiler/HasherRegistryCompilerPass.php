<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass to configure HasherRegistry with configured hashers.
 * 
 * @since 4.0.0
 */
class HasherRegistryCompilerPass implements CompilerPassInterface
{
    /**
     * Configure the HasherRegistry with all configured hashers.
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('pgs_hash_id.hasher_registry')) {
            return;
        }
        
        $registryDefinition = $container->getDefinition('pgs_hash_id.hasher_registry');
        
        // Get list of configured hashers
        $hasherNames = $container->hasParameter('pgs_hash_id.hashers')
            ? $container->getParameter('pgs_hash_id.hashers')
            : ['default'];
        
        // Configure each hasher in the registry
        foreach ($hasherNames as $name) {
            $config = [];
            
            // Get configuration parameters for this hasher
            $saltParam = sprintf('pgs_hash_id.hashers.%s.salt', $name);
            $minLengthParam = sprintf('pgs_hash_id.hashers.%s.min_hash_length', $name);
            $alphabetParam = sprintf('pgs_hash_id.hashers.%s.alphabet', $name);
            
            if ($container->hasParameter($saltParam)) {
                $config['salt'] = $container->getParameter($saltParam);
            }
            if ($container->hasParameter($minLengthParam)) {
                $config['min_hash_length'] = $container->getParameter($minLengthParam);
            }
            if ($container->hasParameter($alphabetParam)) {
                $config['alphabet'] = $container->getParameter($alphabetParam);
            }
            
            // Add method call to register hasher
            if (!empty($config)) {
                $registryDefinition->addMethodCall('registerHasher', [$name, $config]);
            }
        }
        
        // Also check for legacy converter configuration
        if (!in_array('default', $hasherNames, true)) {
            $legacyConfig = [];
            
            if ($container->hasParameter('pgs_hash_id.converter.hashids.salt')) {
                $legacyConfig['salt'] = $container->getParameter('pgs_hash_id.converter.hashids.salt');
            }
            if ($container->hasParameter('pgs_hash_id.converter.hashids.min_hash_length')) {
                $legacyConfig['min_hash_length'] = $container->getParameter('pgs_hash_id.converter.hashids.min_hash_length');
            }
            if ($container->hasParameter('pgs_hash_id.converter.hashids.alphabet')) {
                $legacyConfig['alphabet'] = $container->getParameter('pgs_hash_id.converter.hashids.alphabet');
            }
            
            if (!empty($legacyConfig)) {
                $registryDefinition->addMethodCall('registerHasher', ['default', $legacyConfig]);
            }
        }
    }
}