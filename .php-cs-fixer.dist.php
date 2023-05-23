<?php
// For php-cs-fixer 3.17.0
return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2'                           => true,
        'array_indentation'               => true,
        'array_syntax'                    => [
            'syntax' => 'short'
        ],
        'combine_consecutive_unsets'      => true,
        'binary_operator_spaces'          => [
            'operators' => [
                '='  => 'align_single_space',
                '=>' => 'align_single_space',
            ]
        ],
        'function_typehint_space'         => true,
        'class_attributes_separation'     => [
            'elements' => [
                'method' => 'one'
            ]
        ],
        'braces'                          => [
            'allow_single_line_anonymous_class_with_empty_body' => true,
            'allow_single_line_closure'                         => true,
        ],
        'no_unused_imports'               => true,
        'ordered_imports'                 => true,
        'return_type_declaration'         => [
            'space_before' => 'one'
        ],
        'whitespace_after_comma_in_array' => true,
        'no_superfluous_elseif'           => true,
        'no_useless_else'                 => true,
        'no_whitespace_in_blank_line'     => true,
        'compact_nullable_typehint'       => true,
    ])
    ->setLineEnding("\n")
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude([
                'vendor'
            ])
            ->in([
                __DIR__.'/src/main/php',
            ])
    )
;
