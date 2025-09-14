<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->exclude([
        'cache',
        'logs',
        'var',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        // PSR-12 Base
        '@PSR12' => true,
        '@PSR12:risky' => true,
        
        // PHP 8.1+ Modern Features
        'modernize_types_casting' => true,
        'use_arrow_functions' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters', 'match'],
        ],
        'nullable_type_declaration_for_default_null_value' => true,
        'clean_namespace' => true,
        
        // Strict type declarations
        'declare_strict_types' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'native_function_invocation' => [
            'include' => ['@all'],
            'scope' => 'namespaced',
        ],
        
        // PHP 8.3 specific improvements
        'no_unset_cast' => true,
        'no_useless_sprintf' => true,
        'single_line_throw' => false,
        
        // Code quality and readability
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'no_unused_imports' => true,
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'allow_unused_params' => false,
            'remove_inheritdoc' => false,
        ],
        'phpdoc_order' => true,
        'phpdoc_separation' => true,
        'phpdoc_summary' => true,
        'phpdoc_trim' => true,
        
        // Symfony-specific rules
        'fopen_flag_order' => true,
        'fopen_flags' => ['b_mode' => true],
        
        // Security and best practices
        'random_api_migration' => true,
        'pow_to_exponentiation' => true,
        'is_null' => true,
        'dir_constant' => true,
        'ereg_to_preg' => true,
        'mb_str_functions' => true,
        
        // PSR-4 compliance
        'psr_autoloading' => ['dir' => null],
        'class_definition' => [
            'single_line' => true,
            'single_item_single_line' => true,
            'multi_line_extends_each_single_line' => true,
        ],
        
        // PHPUnit modernization
        'php_unit_test_case_static_method_calls' => [
            'call_type' => 'self',
        ],
        'php_unit_method_casing' => ['case' => 'camel_case'],
        'php_unit_set_up_tear_down_visibility' => true,
        'php_unit_test_class_requires_covers' => false,
        'php_unit_internal_class' => false,
        'php_unit_strict' => true,
        
        // Whitespace and formatting
        'blank_line_after_opening_tag' => false,
        'blank_line_before_statement' => [
            'statements' => [
                'break',
                'continue',
                'declare',
                'return',
                'throw',
                'try',
                'yield',
                'yield_from',
            ],
        ],
        'method_chaining_indentation' => true,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'no_multiple_statements_per_line' => true,
        
        // Exception handling
        'no_useless_else' => true,
        'no_useless_return' => true,
        'simplified_null_return' => true,
        
        // Constants and variables
        'constant_case' => ['case' => 'lower'],
        'magic_constant_casing' => true,
        'magic_method_casing' => true,
        'native_constant_invocation' => ['fix_built_in' => false],
        
        // Comments and documentation
        'comment_to_phpdoc' => [
            'ignored_tags' => ['todo', 'codeCoverageIgnore'],
        ],
        'header_comment' => false,
        'no_empty_comment' => true,
        'single_line_comment_style' => ['comment_types' => ['hash']],
        
        // String handling
        'escape_implicit_backslashes' => [
            'double_quoted' => true,
            'heredoc_syntax' => true,
            'single_quoted' => false,
        ],
        'explicit_string_variable' => true,
        'heredoc_to_nowdoc' => true,
        'string_line_ending' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');