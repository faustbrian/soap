<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Wsdl\DocumentationStrategy;

use ReflectionClass;
use ReflectionProperty;

use function explode;
use function implode;
use function mb_trim;
use function preg_match;
use function preg_replace;

final class ReflectionDocumentation implements DocumentationStrategyInterface
{
    /**
     * @return string
     */
    public function getPropertyDocumentation(ReflectionProperty $property)
    {
        return $this->parseDocComment($property->getDocComment());
    }

    /**
     * @return string
     */
    public function getComplexTypeDocumentation(ReflectionClass $class)
    {
        return $this->parseDocComment($class->getDocComment());
    }

    /**
     * @param  string $docComment
     * @return string
     */
    private function parseDocComment($docComment)
    {
        $documentation = [];

        foreach (explode("\n", $docComment) as $i => $line) {
            if ($i === 0) {
                continue;
            }

            $line = mb_trim(preg_replace('/\s*\*+/', '', $line));

            if (preg_match('/^(@[a-z]|\/)/i', $line)) {
                break;
            }

            // only include newlines if we've already got documentation
            if (empty($documentation) && $line === '') {
                continue;
            }

            $documentation[] = $line;
        }

        return implode("\n", $documentation);
    }
}
