<?php

namespace Francoisvaillant\TwigTrace\Twig\Listener;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\NodeVisitor\NodeVisitorInterface;

class HtmlCommentNodeVisitor implements NodeVisitorInterface
{
    private const COMMENT_STRUCTURE = '<!-- %s -->';
    private const EXCLUDED_TEMPLATES = ['@WebProfiler'];

    public function __construct(
        private readonly ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * @param Node $node
     * @param Environment $env
     * @return Node
     */
    public function enterNode(Node $node, Environment $env): Node
    {
        if ($this->shouldAddComments($node, $env)) {
            $templateName = $node->getTemplateName();

            $node->setNode('display_start', $this->createStartComment($templateName, $node->getTemplateLine()));
            $node->setNode('display_end', $this->createEndComment($templateName, $node->getTemplateLine()));
        }

        return $node;
    }

    /**
     * @param Node $node
     * @param Environment $env
     * @return Node|null
     */
    public function leaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 0;
    }

    /**
     * @param Node $node
     * @param Environment $env
     * @return bool
     */
    private function shouldAddComments(Node $node, Environment $env): bool
    {
        if (!$node instanceof ModuleNode || !$env->isDebug()) {
            return false;
        }

        $templateName = $node->getTemplateName();

        foreach (self::EXCLUDED_TEMPLATES as $excluded) {
            if (str_contains($templateName, $excluded)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $templateName
     * @param int $line
     * @return TextNode
     */
    private function createStartComment(string $templateName, int $line): TextNode
    {
        return $this->createComment(
            $this->buildMessage($templateName),
            $line
        );
    }

    /**
     * @param string $templateName
     * @param int $line
     * @return TextNode
     */
    private function createEndComment(string $templateName, int $line): TextNode
    {
        return $this->createComment(
            $this->buildMessage($templateName, 'end template '),
            $line
        );
    }

    /**
     * @param string $content
     * @param int $line
     * @return TextNode
     */
    private function createComment(string $content, int $line): TextNode
    {
        return new TextNode(
            "\n" . sprintf(self::COMMENT_STRUCTURE, $content) . "\n",
            $line
        );
    }

    /**
     * @param string $templateName
     * @param string $prefix
     * @return string
     */
    private function buildMessage(string $templateName, string $prefix = ''): string
    {
        $separatorStart = $this->getParameter('separator_start');
        $separatorEnd = $this->getParameter('separator_end');

        $parts = array_filter([
            $separatorStart,
            $prefix . $templateName,
            $separatorEnd,
        ]);

        return implode(' ', $parts);
    }

    /**
     * @param string $key
     * @return string
     */
    private function getParameter(string $key): string
    {
        return (string) $this->parameterBag->get($key);
    }
}
