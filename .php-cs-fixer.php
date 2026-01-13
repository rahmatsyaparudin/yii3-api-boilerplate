<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

ini_set('memory_limit', '512M');

$root = __DIR__;
$finder = (new Finder())
    ->in([
        $root . '/config',
        $root . '/src',
        $root . '/tests',
    ])
    ->append([
        $root . '/public/index.php',
    ]);

return (new Config())
    ->setCacheFile(__DIR__ . '/runtime/cache/.php-cs-fixer.cache')
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        '@PER-CS2x0' => true,
        '@PSR12' => true,
        '@Symfony' => true,
        '@PHP8x0Migration' => true,
        '@PHP8x0Migration:risky' => true,
        '@PHP8x1Migration' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'no_superfluous_phpdoc_tags' => false,
        'phpdoc_order' => true,
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'single_line_comment_style' => true,
        'yoda_style' => false,
        'binary_operator_spaces' => [
            'operators' => [
                '=' => 'align_single_space_minimal',
                '=>' => 'align_single_space_minimal',
                '&&' => 'align_single_space_minimal',
                '||' => 'align_single_space_minimal',
            ],
        ],
        'concat_space' => ['spacing' => 'one'],
        'not_operator_with_successor_space' => false,
        'trailing_comma_in_multiline' => true,
        'whitespace_after_comma_in_array' => true,
        'class_definition' => ['single_line' => false],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'native_function_invocation' => [
            'include' => ['@all'],
            'exclude' => [],
            'strict' => true,
        ],
        'no_trailing_whitespace' => true,
        'no_whitespace_in_blank_line' => true,
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'try', 'yield'],
        ],
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'single_import_per_statement' => true,
        'no_leading_import_slash' => true,
        'single_line_after_imports' => true,
    ])
    ->setFinder($finder);
