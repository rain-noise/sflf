<?php
// For php-cs-fixer 3.63.1
// https://github.com/PHP-CS-Fixer/PHP-CS-Fixer#usage
// https://mlocati.github.io/php-cs-fixer-configurator/#version:3.63
return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2'             => true,
        'array_indentation' => true,
        'array_syntax'      => [
            'syntax' => 'short'
        ],
        'combine_consecutive_unsets' => true,
        'binary_operator_spaces'     => [
            'operators' => [
                '='  => 'align_single_space_minimal',
                '=>' => 'align_single_space_minimal',
            ]
        ],
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one'
            ]
        ],
        'braces_position' => [
            'allow_single_line_empty_anonymous_classes' => true,
        ],
        'no_unused_imports'       => true,
        'ordered_imports'         => true,
        'return_type_declaration' => [
            'space_before' => 'one'
        ],
        'whitespace_after_comma_in_array'   => true,
        'no_superfluous_elseif'             => true,
        'no_useless_else'                   => true,
        'no_whitespace_in_blank_line'       => true,
        'compact_nullable_type_declaration' => true,
    ])
    ->setLineEnding("\n")
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude([
                'vendor'
            ])
            ->in([
                __DIR__.'/src/app',
            ])
    )
;
