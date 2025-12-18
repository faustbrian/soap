<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Wsdl\ComplexTypeStrategy;

use Cline\Soap\Wsdl;

use function mb_strpos;
use function mb_strtolower;
use function mb_substr;
use function mb_substr_count;
use function preg_match;
use function str_repeat;
use function str_replace;
use function ucfirst;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class ArrayOfTypeSequence extends DefaultComplexType
{
    /**
     * Add an unbounded ArrayOfType based on the xsd:sequence syntax if
     * type[] is detected in return value doc comment.
     *
     * @return string tns:xsd-type
     */
    public function addComplexType(string $type): string
    {
        $nestedCounter = $this->getNestedCount($type);

        if ($nestedCounter > 0) {
            $singularType = $this->getSingularType($type);
            $complexType = '';

            for ($i = 1; $i <= $nestedCounter; ++$i) {
                $complexType = $this->getTypeBasedOnNestingLevel($singularType, $i);
                $complexTypePhp = $singularType.str_repeat('[]', $i);
                $childType = $this->getTypeBasedOnNestingLevel($singularType, $i - 1);

                $this->addSequenceType($complexType, $childType, $complexTypePhp);
            }

            return $complexType;
        }

        if (($soapType = $this->scanRegisteredTypes($type)) !== null) {
            // Existing complex type
            return $soapType;
        }

        // New singular complex type
        return parent::addComplexType($type);
    }

    /**
     * Return the ArrayOf or simple type name based on the singular xsdtype
     * and the nesting level
     */
    protected function getTypeBasedOnNestingLevel(string $singularType, int $level): string
    {
        if ($level === 0) {
            // This is not an Array anymore, return the xsd simple type
            return $this->requireContext()->getType($singularType);
        }

        return Wsdl::TYPES_NS
            .':'
            .str_repeat('ArrayOf', $level)
            .ucfirst($this->requireContext()->translateType($singularType));
    }

    /**
     * From a nested definition with type[] or array<Type>, get the singular xsd:type
     */
    protected function getSingularType(string $type): string
    {
        // Handle array<Type> notation - unwrap all nested array<> to get innermost type
        while (preg_match('/^array<(.+)>$/i', $type, $matches)) {
            $type = $matches[1];
        }

        // Handle Type[] notation
        return str_replace('[]', '', $type);
    }

    /**
     * Return the array nesting level based on the type name
     */
    protected function getNestedCount(string $type): int
    {
        // Handle array<Type> notation - count nested array< occurrences
        if (preg_match('/^array<.+>$/i', $type)) {
            return mb_substr_count(mb_strtolower($type), 'array<');
        }

        // Handle Type[] notation
        return mb_substr_count($type, '[]');
    }

    /**
     * Append the complex type definition to the WSDL via the context access
     *
     * @param string $arrayType    Array type name (e.g. 'tns:ArrayOfArrayOfInt')
     * @param string $childType    Qualified array items type (e.g. 'xsd:int', 'tns:ArrayOfInt')
     * @param string $phpArrayType PHP type (e.g. 'int[][]', '\MyNamespace\MyClassName[][][]')
     */
    protected function addSequenceType(string $arrayType, string $childType, string $phpArrayType): void
    {
        if ($this->scanRegisteredTypes($phpArrayType) !== null) {
            return;
        }

        // Register type here to avoid recursion
        $this->requireContext()->addType($phpArrayType, $arrayType);

        $dom = $this->requireContext()->toDomDocument();

        $arrayTypeName = mb_substr($arrayType, mb_strpos($arrayType, ':') + 1);

        $complexType = $dom->createElementNS(Wsdl::XSD_NS_URI, 'complexType');
        $this->requireContext()->getSchema()->appendChild($complexType);

        $complexType->setAttribute('name', $arrayTypeName);

        $sequence = $dom->createElementNS(Wsdl::XSD_NS_URI, 'sequence');
        $complexType->appendChild($sequence);

        $element = $dom->createElementNS(Wsdl::XSD_NS_URI, 'element');
        $sequence->appendChild($element);

        $element->setAttribute('name', 'item');
        $element->setAttribute('type', $childType);
        $element->setAttribute('minOccurs', '0');
        $element->setAttribute('maxOccurs', 'unbounded');
    }
}
