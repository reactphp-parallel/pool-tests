<?php declare(strict_types=1);

use WyriHaximus\CsFixerConfig\PhpCsFixerConfig;
use PhpCsFixer\Config;

return (function (): Config
{
    $paths = [
        __DIR__ . DIRECTORY_SEPARATOR . 'src',
    ];

    return PhpCsFixerConfig::create()
        ->setFinder(
            PhpCsFixer\Finder::create()
                ->in($paths)
                ->append($paths)
        )
        ->setUsingCache(false)
        ->setRules([
            'native_function_invocation' => false,
        ])
    ;
})();
