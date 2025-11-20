<?php

/**
 * Bundle class
 */

namespace Francoisvaillant\TwigTrace;

use Francoisvaillant\TwigTrace\DependencyInjection\TwigTraceExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class TwigTraceBundle extends AbstractBundle
{
    /**
     * @return ExtensionInterface|null
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new TwigTraceExtension();
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

}
