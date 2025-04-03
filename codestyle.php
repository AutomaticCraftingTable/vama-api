<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$config = new Config();

$config->setRules([
    '@PSR12' => true,
    'array_syntax' => ['syntax' => 'short'],
    'binary_operator_spaces' => [
        'default' => 'single_space',
    ],
    'braces' => [
        'position_after_functions_and_oop_constructs' => 'next',
    ],
    'function_typehint_space' => true,
    'no_trailing_comma_in_singleline' => true,
    'single_quote' => false,
    'trailing_comma_in_multiline' => true,
]);

$config->setFinder(
    Finder::create()
        ->in(__DIR__)
        ->exclude('vendor')
        ->exclude('cache')
);

return $config;
