<?php

/**
 * Bundle extension.
 */

namespace Francoisvaillant\TwigTrace\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class TwigTraceExtension extends Extension
{
    /**
     * @param array<string, mixed> $configs
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        /** @var string $separatorStart */
        $separatorStart = $config['separator_template_start'];
        /** @var string $separatorEnd */
        $separatorEnd = $config['separator_template_end'];

        /** @var string $separatorMacroStart */
        $separatorMacroStart = $config['separator_macro_start'];
        /** @var string $separatorMacroEnd */
        $separatorMacroEnd = $config['separator_macro_end'];

        /** @var string $separatorBlockStart */
        $separatorBlockStart = $config['separator_block_start'];
        /** @var string $separatorBlockEnd */
        $separatorBlockEnd = $config['separator_block_end'];

        /** @var array<string> $excludedBlocks */
        $excludedBlocks = $config['excluded_blocks'];

        /** @var array<string> $excludedPaths */
        $excludedPaths = $config['excluded_paths'];

        $container->setParameter('separator_template_start', $separatorStart);
        $container->setParameter('separator_template_end', $separatorEnd);
        $container->setParameter('separator_macro_start', $separatorMacroStart);
        $container->setParameter('separator_macro_end', $separatorMacroEnd);
        $container->setParameter('separator_block_start', $separatorBlockStart);
        $container->setParameter('separator_block_end', $separatorBlockEnd);
        $container->setParameter('excluded_blocks', $excludedBlocks);
        $container->setParameter('excluded_paths', $excludedPaths);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return 'twig_trace';
    }
}
