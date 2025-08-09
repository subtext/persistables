<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()->name('.*.php')->in([
    __DIR__ . '/src',
    __DIR__ . '/tests/unit',
]);

$config = new Config();

return $config->setRiskyAllowed(true)->setRules([
    '@PSR12'             => true,
    'array_syntax'       => ['syntax' => 'short'],
    'no_alias_functions' => [
        'sets' => ['@internal']
    ],
    'fully_qualified_strict_types' => true,
    'binary_operator_spaces'       => [
        'default' => 'align_single_space_minimal',
    ],
    'class_attributes_separation' => [
        'elements' => [
            'const'        => 'only_if_meta',
            'property'     => 'only_if_meta',
            'method'       => 'one',
            'trait_import' => 'none',
        ],
    ],
    'function_typehint_space' => true,
    'global_namespace_import' => [
        'import_classes'   => true,
        'import_functions' => true,
        'import_constants' => null,
    ],
    'linebreak_after_opening_tag'             => true,
    'magic_constant_casing'                   => true,
    'magic_method_casing'                     => true,
    'method_chaining_indentation'             => true,
    'native_function_casing'                  => true,
    'native_function_type_declaration_casing' => true,
    'no_extra_blank_lines'                    => [
        'tokens' => [
            'break',
            'case',
            'continue',
            'extra',
            'parenthesis_brace_block',
            'return',
            'square_brace_block',
            'switch',
            'throw',
        ],
    ]
])->setFinder($finder);
