<?php

namespace Francoisvaillant\TwigTrace\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('twig_trace');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('separator_start')
                    ->defaultValue('')
                    ->info('Separator to display at the start of template comments')
                ->end()
                ->scalarNode('separator_end')
                    ->defaultValue('')
                    ->info('Separator to display at the end of template comments')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
