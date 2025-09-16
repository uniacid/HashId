<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PgsHashIdExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $fileLocator = new FileLocator(__DIR__.'/../Resources/config');

        // Use YAML loader for modern Symfony 6.4+ installations
        // Fall back to XML for backward compatibility
        if (\class_exists(YamlFileLoader::class)) {
            $loader = new YamlFileLoader($container, $fileLocator);
            $configFile = 'services.yaml';
        } else {
            $loader = new XmlFileLoader($container, $fileLocator);
            $configFile = 'services.xml';
        }

        $loader->load($configFile);

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Legacy converter configuration (backward compatibility)
        if (isset($config[Configuration::NODE_CONVERTER])) {
            foreach ($config[Configuration::NODE_CONVERTER] as $converter => $parameters) {
                foreach ($parameters as $parameter => $value) {
                    $container->setParameter(\sprintf('pgs_hash_id.converter.%s.%s', $converter, $parameter), $value);
                }
            }
        }
        
        // New multiple hashers configuration with environment variable support
        if (isset($config['hashers'])) {
            foreach ($config['hashers'] as $name => $hasherConfig) {
                foreach ($hasherConfig as $parameter => $value) {
                    // Handle environment variables and typed environment variables
                    if (is_string($value) && str_contains($value, '%env(')) {
                        // Environment variable - pass as-is for Symfony to resolve
                        $container->setParameter(\sprintf('pgs_hash_id.hashers.%s.%s', $name, $parameter), $value);
                    } else {
                        // Regular value
                        $container->setParameter(\sprintf('pgs_hash_id.hashers.%s.%s', $name, $parameter), $value);
                    }
                }
            }
            
            // Store the list of configured hashers
            $container->setParameter('pgs_hash_id.hashers', array_keys($config['hashers']));
            
            // Register hasher configurations as services for validation
            foreach ($config['hashers'] as $name => $hasherConfig) {
                $container->setParameter(
                    \sprintf('pgs_hash_id.hasher_config.%s', $name),
                    $hasherConfig
                );
            }
        } else {
            // If no hashers configured, create default from legacy config
            $container->setParameter('pgs_hash_id.hashers', ['default']);
            if (isset($config[Configuration::NODE_CONVERTER]['hashids'])) {
                $legacyConfig = $config[Configuration::NODE_CONVERTER]['hashids'];
                $container->setParameter('pgs_hash_id.hashers.default.salt', $legacyConfig['salt']);
                $container->setParameter('pgs_hash_id.hashers.default.min_hash_length', $legacyConfig['min_hash_length']);
                $container->setParameter('pgs_hash_id.hashers.default.alphabet', $legacyConfig['alphabet']);
            }
        }

        // Note: AnnotationRegistry::loadAnnotationClass is deprecated in Doctrine Annotations 2.0+
        // Modern autoloading handles this automatically
        // For backward compatibility with annotations, we rely on autoloading
    }
}
