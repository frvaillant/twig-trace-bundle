<?php

/**
 *  This class makes HtmlCommentNodeVisitor available to twig templates
 */

namespace Francoisvaillant\TwigTrace\Twig\Extension;

use Francoisvaillant\TwigTrace\Twig\Listener\HtmlCommentNodeVisitor;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;

class HtmlCommentExtension extends AbstractExtension
{

    public function __construct(
        private ParameterBagInterface $parameterBag,
    )
    {
    }

    /**
     * @return HtmlCommentNodeVisitor[]
     */
    public function getNodeVisitors(): array
    {
        return [new HtmlCommentNodeVisitor($this->parameterBag)];
    }
}
