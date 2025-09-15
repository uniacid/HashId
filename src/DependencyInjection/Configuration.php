<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\DependencyInjection;

use Hashids\Hashids;
use Pgs\HashIdBundle\Config\HashIdConfigInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    // Use typed constants from HashIdConfigInterface for PHP 8.3+
    // Maintaining backward compatibility by referencing interface constants
    public const ROOT_NAME = HashIdConfigInterface::ROOT_NAME;

    public const NODE_CONVERTER = HashIdConfigInterface::NODE_CONVERTER;
    public const NODE_CONVERTER_HASHIDS = HashIdConfigInterface::NODE_CONVERTER_HASHIDS;
    public const NODE_CONVERTER_HASHIDS_SALT = HashIdConfigInterface::NODE_CONVERTER_HASHIDS_SALT;
    public const NODE_CONVERTER_HASHIDS_MIN_HASH_LENGTH = HashIdConfigInterface::NODE_CONVERTER_HASHIDS_MIN_HASH_LENGTH;
    public const NODE_CONVERTER_HASHIDS_ALPHABET = HashIdConfigInterface::NODE_CONVERTER_HASHIDS_ALPHABET;

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NAME);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                // Legacy configuration for backward compatibility
                ->arrayNode(self::NODE_CONVERTER)->addDefaultsIfNotSet()->ignoreExtraKeys(false)
                    ->children()
                        ->append($this->addHashidsConverterNode())
                    ->end()
                ->end()
                // New multiple hashers configuration
                ->arrayNode('hashers')
                    ->useAttributeAsKey('name')
                    ->normalizeKeys(false)  // Allow hyphens and dots in hasher names
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('salt')
                                ->defaultValue(HashIdConfigInterface::DEFAULT_SALT)
                            ->end()
                            ->scalarNode('min_hash_length')
                                ->defaultValue(HashIdConfigInterface::DEFAULT_MIN_LENGTH)
                            ->end()
                            ->scalarNode('alphabet')
                                ->defaultValue(HashIdConfigInterface::DEFAULT_ALPHABET)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    private function addHashidsConverterNode(): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition(self::NODE_CONVERTER_HASHIDS);

        if (!$this->supportsHashids()) {
            return $node;
        }

        return $node
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode(self::NODE_CONVERTER_HASHIDS_SALT)
            ->defaultValue(HashIdConfigInterface::DEFAULT_SALT)
            ->end()
                    /* @scrutinizer ignore-call */
            ->scalarNode(self::NODE_CONVERTER_HASHIDS_MIN_HASH_LENGTH)
            ->defaultValue(HashIdConfigInterface::DEFAULT_MIN_LENGTH)
            ->end()
            ->scalarNode(self::NODE_CONVERTER_HASHIDS_ALPHABET)
            ->defaultValue(HashIdConfigInterface::DEFAULT_ALPHABET)
            ->end()
            ->end();
    }

    public function supportsHashids()
    {
        return \class_exists(Hashids::class);
    }
}
