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

        foreach ($config[Configuration::NODE_CONVERTER] as $converter => $parameters) {
            foreach ($parameters as $parameter => $value) {
                $container->setParameter(\sprintf('pgs_hash_id.converter.%s.%s', $converter, $parameter), $value);
            }
        }

        // Note: AnnotationRegistry::loadAnnotationClass is deprecated in Doctrine Annotations 2.0+
        // Modern autoloading handles this automatically
        // For backward compatibility with annotations, we rely on autoloading
    }
}
