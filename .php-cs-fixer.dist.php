<?php

use PhpCsFixerCustomFixers\Fixer\MultilinePromotedPropertiesFixer;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->notPath('Api/Dto/Cover.php')
;

return (new PhpCsFixer\Config())
    ->registerCustomFixers(new PhpCsFixerCustomFixers\Fixers())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => false,
        'phpdoc_align' => false,
        'no_superfluous_phpdoc_tags' => false,
        'array_syntax' => ['syntax' => 'short'],
        MultilinePromotedPropertiesFixer::name() => true,
    ])
    ->setFinder($finder)
    ->setCacheFile('.php-cs-fixer.cache') // forward compatibility with 3.x line
;
