<?php

declare(strict_types=1);

namespace Francoisvaillant\TwigTrace\Tests\Integration;

use Francoisvaillant\TwigTrace\Twig\Extension\HtmlCommentExtension;
use Francoisvaillant\TwigTrace\Twig\Loader\DebugLoaderDecorator;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Integration test verifying that all bundle components work together
 * to produce correct HTML output with template/block/macro comments.
 */
class FullStackRenderingTest extends TestCase
{
    /**
     * @param array<string,string> $templates
     * @param array<string>        $excludedMacros
     */
    private function createEnvironment(array $templates, bool $debug = true, array $excludedMacros = []): Environment
    {
        $innerLoader = new ArrayLoader($templates);

        $decoratedLoader = new DebugLoaderDecorator(
            $innerLoader,
            $debug,
            '<<<',   // separatorMacroStart
            '>>>',   // separatorMacroEnd
            '<<<',   // separatorBlockStart
            '>>>',   // separatorBlockEnd
            ['head'], // excludedBlocks
            $excludedMacros,
            ['@WebProfiler'], // excludedPaths
        );

        $env = new Environment($decoratedLoader, [
            'debug'      => $debug,
            'cache'      => false,
            'autoescape' => false,
        ]);

        $env->addExtension(new HtmlCommentExtension(
            '<<<',
            '>>>'
        ));

        return $env;
    }

    public function testCompleteHtmlOutputWithInheritanceAndMacros(): void
    {
        $templates = [
            'base.html.twig' => <<<'TWIG'
{% block head %}<title>{% block title %}Default Title{% endblock %}</title>{% endblock %}
{% block content %}Base content{% endblock %}
{% macro button(label) %}<button>{{ label }}</button>{% endmacro %}
TWIG,
            'child.html.twig' => <<<'TWIG'
{% extends 'base.html.twig' %}
{% block title %}Child Title{% endblock %}
{% block content %}
{{ parent() }}
Child content
{% import 'base.html.twig' as macros %}
{{ macros.button('Click me') }}
{% endblock %}
TWIG,
        ];

        $env  = $this->createEnvironment($templates);
        $html = $env->render('child.html.twig');

        // Verify template comments
        $this->assertStringContainsString('<!-- <<< TEMPLATE : child.html.twig >>> -->', $html);
        $this->assertStringContainsString('<!-- <<< END TEMPLATE : child.html.twig >>> -->', $html);

        // Verify block comments (blocks reference the template that overrides them)
        $this->assertStringContainsString('<!-- <<< BLOCK : child.html.twig::title >>> -->', $html);
        $this->assertStringContainsString('<!-- <<< END BLOCK : child.html.twig::title >>> -->', $html);
        $this->assertStringContainsString('<!-- <<< BLOCK : child.html.twig::content >>> -->', $html);
        $this->assertStringContainsString('<!-- <<< END BLOCK : child.html.twig::content >>> -->', $html);

        // The parent block call also appears
        $this->assertStringContainsString('<!-- <<< BLOCK : base.html.twig::content >>> -->', $html);
        $this->assertStringContainsString('<!-- <<< END BLOCK : base.html.twig::content >>> -->', $html);

        // Verify excluded block (head) is NOT wrapped
        $this->assertStringNotContainsString('BLOCK : base.html.twig::head', $html);

        // Verify macro comments
        $this->assertStringContainsString('<!-- <<< MACRO : base.html.twig::button >>> -->', $html);
        $this->assertStringContainsString('<!-- <<< END MACRO : base.html.twig::button >>> -->', $html);

        // Verify actual content is present
        $this->assertStringContainsString('Child Title', $html);
        $this->assertStringContainsString('Base content', $html);
        $this->assertStringContainsString('Child content', $html);
        $this->assertStringContainsString('<button>Click me</button>', $html);
    }

    public function testNestedBlocksAreProperlyWrapped(): void
    {
        $templates = [
            'nested.html.twig' => <<<'TWIG'
{% block outer %}
Outer start
{% block inner %}Inner content{% endblock %}
Outer end
{% endblock %}
TWIG,
        ];

        $env  = $this->createEnvironment($templates);
        $html = $env->render('nested.html.twig');

        // Outer block should be wrapped
        $this->assertStringContainsString('<!-- <<< BLOCK : nested.html.twig::outer >>> -->', $html);
        $this->assertStringContainsString('<!-- <<< END BLOCK : nested.html.twig::outer >>> -->', $html);

        // Verify content is present
        $this->assertStringContainsString('Outer start', $html);
        $this->assertStringContainsString('Inner content', $html);
        $this->assertStringContainsString('Outer end', $html);
    }

    public function testMultipleMacrosInSameTemplate(): void
    {
        $templates = [
            'macros.html.twig' => <<<'TWIG'
{% macro link(url, text) %}<a href="{{ url }}">{{ text }}</a>{% endmacro %}
{% macro icon(name) %}<i class="{{ name }}"></i>{% endmacro %}
{% import _self as m %}
{{ m.link('/home', 'Home') }}
{{ m.icon('fa-user') }}
TWIG,
        ];

        $env  = $this->createEnvironment($templates);
        $html = $env->render('macros.html.twig');

        // Both macros should be wrapped
        $this->assertStringContainsString('<!-- <<< MACRO : macros.html.twig::link >>> -->', $html);
        $this->assertStringContainsString('<!-- <<< END MACRO : macros.html.twig::link >>> -->', $html);
        $this->assertStringContainsString('<!-- <<< MACRO : macros.html.twig::icon >>> -->', $html);
        $this->assertStringContainsString('<!-- <<< END MACRO : macros.html.twig::icon >>> -->', $html);

        // Verify actual output
        $this->assertStringContainsString('<a href="/home">Home</a>', $html);
        $this->assertStringContainsString('<i class="fa-user"></i>', $html);
    }

    public function testExcludedPathsAreNotProcessed(): void
    {
        $templates = [
            '@WebProfiler/toolbar.html.twig' => <<<'TWIG'
{% block toolbar %}Toolbar content{% endblock %}
{% macro item(name) %}Item: {{ name }}{% endmacro %}
TWIG,
        ];

        $env  = $this->createEnvironment($templates);
        $html = $env->render('@WebProfiler/toolbar.html.twig');

        // No comments should be added for excluded paths
        $this->assertStringNotContainsString('TEMPLATE :', $html);
        $this->assertStringNotContainsString('BLOCK :', $html);
        $this->assertStringNotContainsString('MACRO :', $html);

        // But content should still render
        $this->assertStringContainsString('Toolbar content', $html);
    }

    public function testDebugDisabledProducesCleanOutput(): void
    {
        $templates = [
            'clean.html.twig' => <<<'TWIG'
{% block content %}Hello World{% endblock %}
{% macro test() %}Test{% endmacro %}
TWIG,
        ];

        $env  = $this->createEnvironment($templates, false);
        $html = $env->render('clean.html.twig');

        // No debug comments when debug is disabled
        $this->assertStringNotContainsString('<!--', $html);
        $this->assertStringNotContainsString('TEMPLATE', $html);
        $this->assertStringNotContainsString('BLOCK', $html);
        $this->assertStringNotContainsString('MACRO', $html);

        // Content still renders
        $this->assertStringContainsString('Hello World', $html);
    }

    public function testComplexInheritanceChain(): void
    {
        $templates = [
            'grandparent.html.twig' => <<<'TWIG'
{% block header %}Grandparent Header{% endblock %}
{% block content %}Grandparent Content{% endblock %}
TWIG,
            'parent.html.twig' => <<<'TWIG'
{% extends 'grandparent.html.twig' %}
{% block header %}{{ parent() }} + Parent Header{% endblock %}
TWIG,
            'child.html.twig' => <<<'TWIG'
{% extends 'parent.html.twig' %}
{% block content %}Child Content{% endblock %}
TWIG,
        ];

        $env  = $this->createEnvironment($templates);
        $html = $env->render('child.html.twig');

        // Template comment for the rendered template
        $this->assertStringContainsString('<!-- <<< TEMPLATE : child.html.twig >>> -->', $html);

        // Block comments reference the templates that override them
        // Header is overridden in parent.html.twig
        $this->assertStringContainsString('BLOCK : parent.html.twig::header', $html);
        // But parent() call shows the grandparent block too
        $this->assertStringContainsString('BLOCK : grandparent.html.twig::header', $html);

        // Content is overridden in child.html.twig
        $this->assertStringContainsString('BLOCK : child.html.twig::content', $html);

        // Verify content
        $this->assertStringContainsString('Grandparent Header', $html);
        $this->assertStringContainsString('Parent Header', $html);
        $this->assertStringContainsString('Child Content', $html);
    }

    public function testIncludeTemplateWithMacros(): void
    {
        $templates = [
            'macros_library.html.twig' => <<<'TWIG'
{% macro alert(type, message) %}<div class="alert-{{ type }}">{{ message }}</div>{% endmacro %}
TWIG,
            'page.html.twig' => <<<'TWIG'
{% import 'macros_library.html.twig' as alerts %}
{% block content %}
{{ alerts.alert('success', 'Operation completed!') }}
{% endblock %}
TWIG,
        ];

        $env  = $this->createEnvironment($templates);
        $html = $env->render('page.html.twig');

        // Template comments
        $this->assertStringContainsString('<!-- <<< TEMPLATE : page.html.twig >>> -->', $html);

        // Block comments
        $this->assertStringContainsString('<!-- <<< BLOCK : page.html.twig::content >>> -->', $html);

        // Macro comments from imported template
        $this->assertStringContainsString('<!-- <<< MACRO : macros_library.html.twig::alert >>> -->', $html);

        // Verify rendered output
        $this->assertStringContainsString('<div class="alert-success">Operation completed!</div>', $html);
    }

    public function testExcludedImportedMacroStillRendersWithoutComments(): void
    {
        $templates = [
            'macros_library.html.twig' => <<<'TWIG'
{% macro alert(type, message) %}<div class="alert-{{ type }}">{{ message }}</div>{% endmacro %}
{% macro icon(name) %}<i class="{{ name }}"></i>{% endmacro %}
TWIG,
            'page.html.twig' => <<<'TWIG'
{% import 'macros_library.html.twig' as ui %}
{% block content %}
{{ ui.alert('success', 'Operation completed!') }}
{{ ui.icon('fa-check') }}
{% endblock %}
TWIG,
        ];

        $env  = $this->createEnvironment($templates, true, ['macros_library.html.twig::alert']);
        $html = $env->render('page.html.twig');

        $this->assertStringNotContainsString('MACRO : macros_library.html.twig::alert', $html);
        $this->assertStringContainsString('MACRO : macros_library.html.twig::icon', $html);
        $this->assertStringContainsString('<div class="alert-success">Operation completed!</div>', $html);
        $this->assertStringContainsString('<i class="fa-check"></i>', $html);
    }

    public function testGlobalMacroExclusionAppliesAcrossMultipleTemplates(): void
    {
        $templates = [
            'form_macros.html.twig' => <<<'TWIG'
{% macro input(name) %}<input name="{{ name }}">{% endmacro %}
{% macro button(label) %}<button>{{ label }}</button>{% endmacro %}
TWIG,
            'filter_macros.html.twig' => <<<'TWIG'
{% macro input(name) %}<label>{{ name }}</label>{% endmacro %}
TWIG,
            'page.html.twig' => <<<'TWIG'
{% import 'form_macros.html.twig' as form %}
{% import 'filter_macros.html.twig' as filters %}
{% block content %}
{{ form.input('email') }}
{{ form.button('Save') }}
{{ filters.input('status') }}
{% endblock %}
TWIG,
        ];

        $env  = $this->createEnvironment($templates, true, ['input']);
        $html = $env->render('page.html.twig');

        $this->assertStringNotContainsString('MACRO : form_macros.html.twig::input', $html);
        $this->assertStringNotContainsString('MACRO : filter_macros.html.twig::input', $html);
        $this->assertStringContainsString('MACRO : form_macros.html.twig::button', $html);
        $this->assertStringContainsString('<input name="email">', $html);
        $this->assertStringContainsString('<button>Save</button>', $html);
        $this->assertStringContainsString('<label>status</label>', $html);
    }
}
