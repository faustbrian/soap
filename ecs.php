<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\CodingStandard\EasyCodingStandard\Factory;
use PhpCsFixer\Fixer\ClassNotation\FinalClassFixer;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixerCustomFixers\Fixer\NoNullableBooleanTypeFixer;

return Factory::create(
    paths: [__DIR__.'/src', __DIR__.'/tests'],
    skip: [
        // These classes need to be extended by subclasses
        FinalClassFixer::class => [
            __DIR__.'/src/Client.php',
            __DIR__.'/src/Wsdl/ComplexTypeStrategy/DefaultComplexType.php',
        ],
        // Keep FQN in docblocks for test fixtures (needed for reflection-based type resolution)
        FullyQualifiedStrictTypesFixer::class => [
            __DIR__.'/tests/Fixtures/commontypes.php',
        ],
        // These classes use ?bool for optional configuration (null = not set)
        NoNullableBooleanTypeFixer::class => [
            __DIR__.'/src/Client.php',
            __DIR__.'/src/Server.php',
        ],
    ],
);
