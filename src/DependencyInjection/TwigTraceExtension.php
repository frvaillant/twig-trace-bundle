<?php

namespace Francoisvaillant\TwigTrace\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class TwigTraceExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('separator_start', $config['separator_start']);
        $container->setParameter('separator_end', $config['separator_end']);
        $container->setParameter('excluded_blocks', $config['excluded_blocks']);
        $container->setParameter('excluded_paths', $config['excluded_paths']);

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
