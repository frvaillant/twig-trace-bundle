<?php

namespace Francoisvaillant\TwigTrace\Twig\Loader;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;
use Twig\Source;

class DebugLoaderDecorator implements LoaderInterface
{
    private const EXCLUDED_TEMPLATES = ['@WebProfiler'];

    public function __construct(
        private readonly LoaderInterface $loader,
        private readonly bool $debug,
        private readonly string $separatorStart,
        private readonly string $separatorEnd,
        private readonly array $excludedBlocks,
        private readonly array $excludedPaths,
    ) {
    }

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

    public function getCacheKey(string $name): string
    {
        return $this->loader->getCacheKey($name);
    }

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
            if (str_contains($name, $excluded)) {
                return true;
            }
        }

        return false;
    }

    private function wrapMacros(string $code, string $templateName): string
    {

        // Regex pour détecter les macros : {% macro nom(params) %}...{% endmacro %}
        $pattern = '/{%\s*macro\s+(\w+)\s*\((.*?)\)\s*%}(.*?){%\s*endmacro\s*%}/s';

        return preg_replace_callback($pattern, function ($matches) use ($templateName) {

            $macroName = $matches[1];
            $macroParams = $matches[2];
            $macroContent = $matches[3];

            return sprintf(
                "{%% macro %s(%s) %%}\n\n<!-- %s MACRO : %s::%s %s -->\n%s\n<!-- %s END MACRO : %s::%s %s -->\n{%% endmacro %%}",
                $macroName,
                $macroParams,
                $this->separatorStart,
                $templateName,
                $macroName,
                $this->separatorEnd,
                $macroContent,
                $this->separatorStart,
                $templateName,
                $macroName,
                $this->separatorEnd
            );
        }, $code);
    }

    private function wrapBlocks(string $code, string $templateName): string
    {
        $pattern = '/{%\s*block\s+(\w+)\s*%}(.*?){%\s*endblock\s*%}/s';

        return preg_replace_callback($pattern, function ($matches) use ($templateName) {

            $blockName = $matches[1];
            $blockContent = $matches[2];

            if (in_array($blockName, $this->excludedBlocks, true)) {
                return $matches[0];
            }

            return sprintf(
                "{%% block %s %%}\n\n<!-- %s BLOCK : %s::%s %s -->\n%s\n<!-- %s END BLOCK : %s::%s %s -->\n{%% endblock %%}",
                $blockName,
                $this->separatorStart,
                $templateName,
                $blockName,
                $this->separatorEnd,
                $blockContent,
                $this->separatorStart,
                $templateName,
                $blockName,
                $this->separatorEnd
            );
        }, $code);
    }


}
