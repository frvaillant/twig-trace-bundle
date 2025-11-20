<?php

/**
 * This class insert HTML comments in twig templates to help debug.
 */

namespace Francoisvaillant\TwigTrace\Twig\Listener;

use Twig\Environment;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\NodeVisitor\NodeVisitorInterface;

class HtmlCommentNodeVisitor implements NodeVisitorInterface
{
    private const COMMENT_STRUCTURE  = '<!-- %s -->';
    private const EXCLUDED_TEMPLATES = ['@WebProfiler'];

    public function __construct(
        private readonly string $separatorTemplateStart,
        private readonly string $separatorTemplateEnd,
    ) {
    }

    public function enterNode(Node $node, Environment $env): Node
    {
        if ($this->shouldAddComments($node, $env)) {
            $templateName = (string) $node->getTemplateName();

            $node->setNode('display_start', $this->createStartComment($templateName, $node->getTemplateLine()));
            $node->setNode('display_end', $this->createEndComment($templateName, $node->getTemplateLine()));
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    public function getPriority(): int
    {
        return 0;
    }

    private function shouldAddComments(Node $node, Environment $env): bool
    {
        if (!$node instanceof ModuleNode || !$env->isDebug()) {
            return false;
        }

        $templateName = (string) $node->getTemplateName();

        foreach (self::EXCLUDED_TEMPLATES as $excluded) {
            if (str_contains($templateName, $excluded)) {
                return false;
            }
        }

        return true;
    }

    private function createStartComment(string $templateName, int $line): TextNode
    {
        return $this->createComment(
            $this->buildMessage($templateName, 'TEMPLATE : '),
            $line
        );
    }

    private function createEndComment(string $templateName, int $line): TextNode
    {
        return $this->createComment(
            $this->buildMessage($templateName, 'END TEMPLATE : '),
            $line
        );
    }

    private function createComment(string $content, int $line): TextNode
    {
        return new TextNode(
            "\n" . sprintf(self::COMMENT_STRUCTURE, $content) . "\n",
            $line
        );
    }

    private function buildMessage(string $templateName, string $prefix = ''): string
    {
        /** @var string $separatorStart */
        $separatorStart = (string)$this->separatorTemplateStart;

        /** @var string $separatorEnd */
        $separatorEnd = (string)$this->separatorTemplateEnd;

        $parts = array_filter([
            $separatorStart,
            $prefix . $templateName,
            $separatorEnd,
        ]);

        return implode(' ', $parts);
    }
}
