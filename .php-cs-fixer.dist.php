<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'yoda_style' => false,
        'ordered_imports' => true,
        'single_quote' => true,
        'no_unused_imports' => true,
        'binary_operator_spaces' => [
            'default' => 'align_single_space',
            'operators' => [
                '=>' => 'align',
                '='  => 'align',
            ],
        ],
        'line_ending' => true,
        'concat_space' => [
            'spacing' => 'one',
        ],
    ])
    ->setFinder($finder);
