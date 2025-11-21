<?php

declare(strict_types=1);

namespace Francoisvaillant\TwigTrace\Tests\Twig\Loader;

use Francoisvaillant\TwigTrace\Twig\Loader\DebugLoaderDecorator;
use PHPUnit\Framework\TestCase;
use Twig\Loader\LoaderInterface;
use Twig\Source;

class DebugLoaderDecoratorTest extends TestCase
{
    private function getBaseTemplate(): string
    {
        return <<<'TWIG'
{% block title %}Title here{% endblock %}
{% block content %}Hello{% endblock %}

{% macro item(name) %}Item: {{ name }}{% endmacro %}
TWIG;
    }

    private function makeInnerLoader(string $code, string $name = 'templates/base.html.twig'): LoaderInterface
    {
        return new class($code, $name) implements LoaderInterface {
            public function __construct(private string $code, private string $name)
            {
            }

            public function getSourceContext(string $name): Source
            {
                return new Source($this->code, $this->name);
            }

            public function getCacheKey(string $name): string
            {
                return $name;
            }

            public function isFresh(string $name, int $time): bool
            {
                return true;
            }

            public function exists(string $name): bool
            {
                return true;
            }
        };
    }

    public function testWrapsBlocksAndMacrosWhenDebugEnabled(): void
    {
        $inner     = $this->makeInnerLoader($this->getBaseTemplate());
        $decorator = new DebugLoaderDecorator(
            $inner,
            true,
            '<<<',   // separatorMacroStart
            '>>>',   // separatorMacroEnd
            '<<<',   // separatorBlockStart
            '>>>',   // separatorBlockEnd
            [],      // excludedBlocks
            [],      // excludedPaths
        );

        $source = $decorator->getSourceContext('templates/base.html.twig');
        $code   = $source->getCode();

        $this->assertStringContainsString('<!-- <<< BLOCK : templates/base.html.twig::content >>> -->', $code);
        $this->assertStringContainsString('<!-- <<< END BLOCK : templates/base.html.twig::content >>> -->', $code);
        $this->assertStringContainsString('<!-- <<< MACRO : templates/base.html.twig::item >>> -->', $code);
        $this->assertStringContainsString('<!-- <<< END MACRO : templates/base.html.twig::item >>> -->', $code);
    }

    public function testExcludedBlocksAreNotWrapped(): void
    {
        $inner     = $this->makeInnerLoader($this->getBaseTemplate());
        $decorator = new DebugLoaderDecorator(
            $inner,
            true,
            '',   // separatorMacroStart
            '',   // separatorMacroEnd
            '',   // separatorBlockStart
            '',   // separatorBlockEnd
            ['title'],      // excludedBlocks
            [],      // excludedPaths
        );

        $code = $decorator->getSourceContext('templates/base.html.twig')->getCode();

        // Title block remains untouched
        $this->assertStringNotContainsString('BLOCK : templates/base.html.twig::title', $code);
        // Content block is wrapped
        $this->assertStringContainsString('BLOCK : templates/base.html.twig::content', $code);
    }

    public function testExcludedPathsBypassWrapping(): void
    {
        $inner     = $this->makeInnerLoader($this->getBaseTemplate(), 'admin/panel.html.twig');
        $decorator = new DebugLoaderDecorator(
            $inner,
            true,
            '',   // separatorMacroStart
            '',   // separatorMacroEnd
            '',   // separatorBlockStart
            '',   // separatorBlockEnd
            [],      // excludedBlocks
            ['admin/'],      // excludedPaths
        );

        $source = $decorator->getSourceContext('admin/panel.html.twig');
        $this->assertSame($this->getBaseTemplate(), $source->getCode());
    }

    public function testNoWrappingWhenDebugDisabled(): void
    {
        $inner     = $this->makeInnerLoader($this->getBaseTemplate());
        $decorator = new DebugLoaderDecorator(
            $inner,
            false,
            '',   // separatorMacroStart
            '',   // separatorMacroEnd
            '',   // separatorBlockStart
            '',   // separatorBlockEnd
            [],      // excludedBlocks
            [],      // excludedPaths
        );

        $source = $decorator->getSourceContext('templates/base.html.twig');
        $this->assertSame($this->getBaseTemplate(), $source->getCode());
    }
}
