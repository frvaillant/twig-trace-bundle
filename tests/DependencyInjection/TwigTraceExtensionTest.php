<?php

declare(strict_types=1);

namespace Francoisvaillant\TwigTrace\Tests\DependencyInjection;

use Francoisvaillant\TwigTrace\DependencyInjection\Configuration;
use Francoisvaillant\TwigTrace\DependencyInjection\TwigTraceExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TwigTraceExtensionTest extends TestCase
{
    public function testGetAlias(): void
    {
        $ext = new TwigTraceExtension();
        $this->assertSame('twig_trace', $ext->getAlias());
    }

    /**
     * @throws \Exception
     */
    public function testLoadSetsParameters(): void
    {
        $ext       = new TwigTraceExtension();
        $container = new ContainerBuilder();

        $processor     = new Processor();
        $configuration = new Configuration();

        /** @var array<string,mixed> $processedConfig */
        $processedConfig = $processor->processConfiguration(
            $configuration,
            [[
                'separator_macro_start'    => '<<<',
                'separator_macro_end'      => '>>>',
                'separator_block_start'    => '[[[',
                'separator_block_end'      => ']]]',
                'separator_template_start' => '///',
                'separator_template_end'   => '///',
                'excluded_blocks'          => ['a', 'b'],
                'excluded_macros'          => ['item', 'base.html.twig::button'],
                'excluded_paths'           => ['admin', 'debug'],
            ]]
        );

        $ext->load([$processedConfig], $container);

        $this->assertSame('<<<', $container->getParameter('separator_macro_start'));
        $this->assertSame('>>>', $container->getParameter('separator_macro_end'));
        $this->assertSame('[[[', $container->getParameter('separator_block_start'));
        $this->assertSame(']]]', $container->getParameter('separator_block_end'));
        $this->assertSame('///', $container->getParameter('separator_template_start'));
        $this->assertSame('///', $container->getParameter('separator_template_end'));
        $this->assertSame(['a', 'b'], $container->getParameter('excluded_blocks'));
        $this->assertSame(['item', 'base.html.twig::button'], $container->getParameter('excluded_macros'));
        $this->assertSame(['admin', 'debug'], $container->getParameter('excluded_paths'));
    }
}
