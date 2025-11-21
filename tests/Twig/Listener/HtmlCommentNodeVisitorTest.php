<?php

declare(strict_types=1);

namespace Francoisvaillant\TwigTrace\Tests\Twig\Listener;

use Francoisvaillant\TwigTrace\Twig\Extension\HtmlCommentExtension;
use Francoisvaillant\TwigTrace\Twig\Listener\HtmlCommentNodeVisitor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class HtmlCommentNodeVisitorTest extends TestCase
{
    private function createTwig(ParameterBagInterface $bag, bool $debug): Environment
    {
        $env = new Environment(new ArrayLoader(), [
            'debug'      => $debug,
            'cache'      => false,
            'autoescape' => false,
        ]);

        $env->addExtension(new HtmlCommentExtension(
            '<<<',
            '>>>',
        ));

        return $env;
    }

    public function testAddsTemplateCommentsInDebugModeWithSeparators(): void
    {
        $bag = new ParameterBag([
            'separator_start' => '<<<',
            'separator_end'   => '>>>',
        ]);

        $env = $this->createTwig($bag, true);
        $env->setLoader(new ArrayLoader([
            'base.html.twig' => 'Hello',
        ]));

        $html = $env->render('base.html.twig');

        $this->assertStringContainsString('<!-- <<< TEMPLATE : base.html.twig >>> -->', $html);
        $this->assertStringContainsString('<!-- <<< END TEMPLATE : base.html.twig >>> -->', $html);
    }

    public function testDoesNotAddCommentsInNonDebugMode(): void
    {
        $bag = new ParameterBag([
            'separator_start' => '',
            'separator_end'   => '',
        ]);
        $env = $this->createTwig($bag, false);
        $env->setLoader(new ArrayLoader([
            'simple' => 'Hi',
        ]));

        $html = $env->render('simple');

        $this->assertSame('Hi', trim($html));
    }

    public function testExcludesWebProfilerTemplates(): void
    {
        $bag = new ParameterBag([
            'separator_start' => '',
            'separator_end'   => '',
        ]);
        $env = $this->createTwig($bag, true);
        $env->setLoader(new ArrayLoader([
            '@WebProfiler/toolbar.html.twig' => 'Toolbar',
        ]));

        $html = $env->render('@WebProfiler/toolbar.html.twig');

        // Should not be wrapped
        $this->assertSame('Toolbar', trim($html));
    }

    public function testExtensionReturnsNodeVisitor(): void
    {
        $bag      = new ParameterBag();
        $ext      = new HtmlCommentExtension(
            '<<<',
            '>>>'
        );
        $visitors = $ext->getNodeVisitors();
        $this->assertCount(1, $visitors);
        $this->assertInstanceOf(HtmlCommentNodeVisitor::class, $visitors[0]);
    }
}
