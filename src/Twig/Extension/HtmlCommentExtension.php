<?php

/**
 *  This class makes HtmlCommentNodeVisitor available to twig templates.
 */

namespace Francoisvaillant\TwigTrace\Twig\Extension;

use Francoisvaillant\TwigTrace\Twig\Listener\HtmlCommentNodeVisitor;
use Twig\Extension\AbstractExtension;

class HtmlCommentExtension extends AbstractExtension
{
    public function __construct(
        private readonly string $separatorTemplateStart,
        private readonly string $separatorTemplateEnd,
    ) {
    }

    /**
     * @return HtmlCommentNodeVisitor[]
     */
    public function getNodeVisitors(): array
    {
        return [new HtmlCommentNodeVisitor($this->separatorTemplateStart, $this->separatorTemplateEnd)];
    }
}
