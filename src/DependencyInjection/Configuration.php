<?php

/**
 * Bundle configuration.
 */

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
                ->scalarNode('separator_template_start')
                    ->defaultValue('')
                    ->info('Separator to display at the start of template comments')
                ->end()
                ->scalarNode('separator_template_end')
                    ->defaultValue('')
                    ->info('Separator to display at the end of template comments')
                ->end()
                ->scalarNode('separator_macro_start')
                    ->defaultValue('')
                    ->info('Separator to display at the start of template comments')
                ->end()
                ->scalarNode('separator_macro_end')
                    ->defaultValue('')
                    ->info('Separator to display at the end of template comments')
                ->end()
                ->scalarNode('separator_block_start')
                    ->defaultValue('')
                    ->info('Separator to display at the start of template comments')
                ->end()
                ->scalarNode('separator_block_end')
                    ->defaultValue('')
                    ->info('Separator to display at the end of template comments')
                ->end()
                ->arrayNode('excluded_blocks')
                    ->defaultValue(['title', 'meta', 'stylesheets', 'javascripts'])
                    ->info('List of block names to exclude from wrapping')
                ->scalarPrototype()->end()
                ->end()
                ->arrayNode('excluded_paths')
                    ->defaultValue([])
                    ->info('List of template paths to exclude from wrapping (supports partial matches)')->scalarPrototype()->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
