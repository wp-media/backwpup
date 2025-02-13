<?php

declare(strict_types=1);

use Devbanana\FixerConfig\Configurator;
use Devbanana\FixerConfig\PhpVersion;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->exclude('tests/bootstrap.php')
    ->exclude('inc')
    ->exclude('packages')
    ->in(__DIR__)
    ->append([
        // Fixer, fix thyself!
        __FILE__,
    ])
;

return Configurator::fromPhpVersion(PhpVersion::PHP_72())
    ->fixerConfig()
    ->setFinder($finder)
;
