<?php

declare(strict_types=1);

namespace Francoisvaillant\TwigTrace\Tests\DependencyInjection;

use Francoisvaillant\TwigTrace\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testDefaultsAreSet(): void
    {
        $configuration = new Configuration();
        $processor     = new Processor();

        $processed = $processor->processConfiguration($configuration, []);

        $this->assertSame('', $processed['separator_macro_start']);
        $this->assertSame('', $processed['separator_macro_end']);
        $this->assertSame('', $processed['separator_block_start']);
        $this->assertSame('', $processed['separator_block_end']);
        $this->assertSame('', $processed['separator_template_start']);
        $this->assertSame('', $processed['separator_template_end']);
        $this->assertSame(['title', 'meta', 'stylesheets', 'javascripts'], $processed['excluded_blocks']);
        $this->assertSame([], $processed['excluded_macros']);
        $this->assertSame([], $processed['excluded_paths']);
    }

    public function testCustomValuesOverrideDefaults(): void
    {
        $configuration = new Configuration();
        $processor     = new Processor();

        $configs = [[
            'separator_macro_start'    => '<<<',
            'separator_macro_end'      => '>>>',
            'separator_block_start'    => '[[[',
            'separator_block_end'      => ']]]',
            'separator_template_start' => '///',
            'separator_template_end'   => '///',
            'excluded_blocks'          => ['head', 'scripts', 'base.html.twig::content'],
            'excluded_macros'          => ['input', 'forms/macros.html.twig::textarea'],
            'excluded_paths'           => ['admin/', '@WebProfiler'],
        ]];

        $processed = $processor->processConfiguration($configuration, $configs);

        $this->assertSame('<<<', $processed['separator_macro_start']);
        $this->assertSame('>>>', $processed['separator_macro_end']);
        $this->assertSame('[[[', $processed['separator_block_start']);
        $this->assertSame(']]]', $processed['separator_block_end']);
        $this->assertSame('///', $processed['separator_template_start']);
        $this->assertSame('///', $processed['separator_template_end']);
        $this->assertSame(['head', 'scripts', 'base.html.twig::content'], $processed['excluded_blocks']);
        $this->assertSame(['input', 'forms/macros.html.twig::textarea'], $processed['excluded_macros']);
        $this->assertSame(['admin/', '@WebProfiler'], $processed['excluded_paths']);
    }
}
