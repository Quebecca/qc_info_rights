<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/Classes')
    ->in(__DIR__ . '/Tests')
    ->in(__DIR__ . '/Configuration')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,
        'strict_types_declaration_equals' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_line_empty_body' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;
