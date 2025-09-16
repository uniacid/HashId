<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures\ConfigurationModernization;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('pgs_hash_id');
        $rootNode = $treeBuilder->getRootNode();
        
        $rootNode
            ->children()
                ->scalarNode('salt')
                    ->defaultValue('')
                ->end()
                ->integerNode('min_hash_length')
                    ->defaultValue(0)
                ->end()
                ->scalarNode('alphabet')
                    ->defaultValue('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890')
                ->end()
            ->end();
            
        return $treeBuilder;
    }
}
?>
-----
<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures\ConfigurationModernization;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration
{
    private const CONFIG_SALT = 'salt';
    private const CONFIG_MIN_HASH_LENGTH = 'min_hash_length';
    private const CONFIG_ALPHABET = 'alphabet';
    private const DEFAULT_ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    
    /**
     * @modernize Add environment variable support and validation
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('pgs_hash_id');
        $rootNode = $treeBuilder->getRootNode();
        
        $rootNode
            ->children()
                ->scalarNode('salt')
                    ->defaultValue('')
                ->end()
                ->integerNode('min_hash_length')
                    ->defaultValue(0)
                ->end()
                ->scalarNode('alphabet')
                    ->defaultValue('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890')
                ->end()
            ->end();
            
        return $treeBuilder;
    }
    
    private function getEnvOrDefault(string $key, mixed $default): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}
?>