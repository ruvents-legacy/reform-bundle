<?php

namespace Ruvents\ReformBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ruvents_reform');

        $rootNode
            ->children()
                ->arrayNode('stateful_file')
                    ->canBeDisabled()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
