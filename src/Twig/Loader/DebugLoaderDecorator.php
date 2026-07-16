<?php

/**
 * This class decorates Twig's loader to add HTML comments to twig templates for blocks and macros
 * For example, the comments are formatted like this : '<!-- BLOCK : templateName::blockName -->.
 */

namespace Francoisvaillant\TwigTrace\Twig\Loader;

use Twig\Loader\LoaderInterface;
use Twig\Source;

class DebugLoaderDecorator implements LoaderInterface
{
    private const EXCLUDED_TEMPLATES = ['@WebProfiler'];

    /**
     * @param array<string> $excludedBlocks
     * @param array<string> $excludedMacros
     * @param array<string> $excludedPaths
     */
    public function __construct(
        private readonly LoaderInterface $loader,
        private readonly bool $debug,
        private readonly string $separatorMacroStart,
        private readonly string $separatorMacroEnd,
        private readonly string $separatorBlockStart,
        private readonly string $separatorBlockEnd,
        private readonly array $excludedBlocks,
        private readonly array $excludedMacros,
        private readonly array $excludedPaths,
    ) {
    }

    /**
     * @throws \Twig\Error\LoaderError
     */
    public function getSourceContext(string $name): Source
    {
        $source = $this->loader->getSourceContext($name);

        if (!$this->debug || $this->shouldExclude($name)) {
            return $source;
        }

        $code = $source->getCode();

        // On wrappe les macros et les blocks, pas le template complet
        // Le NodeVisitor s'occupe déjà des templates
        $code = $this->wrapMacros($code, $name);
        $code = $this->wrapBlocks($code, $name);

        return new Source($code, $source->getName(), $source->getPath());
    }

    /**
     * @throws \Twig\Error\LoaderError
     */
    public function getCacheKey(string $name): string
    {
        return $this->loader->getCacheKey($name);
    }

    /**
     * @throws \Twig\Error\LoaderError
     */
    public function isFresh(string $name, int $time): bool
    {
        return $this->loader->isFresh($name, $time);
    }

    public function exists(string $name): bool
    {
        return $this->loader->exists($name);
    }

    private function shouldExclude(string $name): bool
    {
        foreach ([...self::EXCLUDED_TEMPLATES, ...$this->excludedPaths] as $excluded) {
            if (str_contains((string) $name, (string) $excluded)) {
                return true;
            }
        }

        return false;
    }

    private function wrapMacros(string $code, string $templateName): string
    {
        $pattern = '/{%\s*macro\s+(\w+)\s*\((.*?)\)\s*%}(.*?){%\s*endmacro\s*%}/s';

        $result = preg_replace_callback($pattern, function ($matches) use ($templateName) {
            $macroName    = $matches[1];
            $macroParams  = $matches[2];
            $macroContent = $matches[3];

            if ($this->shouldExcludeMacro($templateName, $macroName)) {
                return $matches[0];
            }

            return sprintf(
                "{%% macro %s(%s) %%}\n\n<!-- %s MACRO : %s::%s %s -->\n%s\n<!-- %s END MACRO : %s::%s %s -->\n{%% endmacro %%}",
                $macroName,
                $macroParams,
                $this->separatorMacroStart,
                $templateName,
                $macroName,
                $this->separatorMacroEnd,
                $macroContent,
                $this->separatorMacroStart,
                $templateName,
                $macroName,
                $this->separatorMacroEnd
            );
        }, $code);

        return $result ?? $code;
    }

    private function shouldExcludeMacro(string $templateName, string $macroName): bool
    {
        return $this->matchesScopedExclusion($this->excludedMacros, $templateName, $macroName);
    }

    private function shouldExcludeBlock(string $templateName, string $blockName): bool
    {
        return $this->matchesScopedExclusion($this->excludedBlocks, $templateName, $blockName);
    }

    private function wrapBlocks(string $code, string $templateName): string
    {
        $pattern = '/{%\s*block\s+(\w+)\s*%}(.*?){%\s*endblock\s*%}/s';

        $result = preg_replace_callback($pattern, function ($matches) use ($templateName) {
            $blockName    = $matches[1];
            $blockContent = $matches[2];

            if ($this->shouldExcludeBlock($templateName, $blockName)) {
                return $matches[0];
            }

            return sprintf(
                "{%% block %s %%}\n\n<!-- %s BLOCK : %s::%s %s -->\n%s\n<!-- %s END BLOCK : %s::%s %s -->\n{%% endblock %%}",
                $blockName,
                $this->separatorBlockStart,
                $templateName,
                $blockName,
                $this->separatorBlockEnd,
                $blockContent,
                $this->separatorBlockStart,
                $templateName,
                $blockName,
                $this->separatorBlockEnd
            );
        }, $code);

        return $result ?? $code;
    }

    /**
     * @param array<string> $excludedEntries
     */
    private function matchesScopedExclusion(array $excludedEntries, string $templateName, string $name): bool
    {
        return in_array($name, $excludedEntries, true)
            || in_array(sprintf('%s::%s', $templateName, $name), $excludedEntries, true);
    }
}
