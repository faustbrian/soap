<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Wsdl\ComplexTypeStrategy;

use Cline\Soap\Exception\InvalidArgumentException;
use Cline\Soap\Wsdl;
use Override;

use function mb_substr_count;
use function preg_match;
use function str_replace;
use function throw_if;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class ArrayOfTypeComplex extends DefaultComplexType
{
    /**
     * Add an ArrayOfType based on the xsd:complexType syntax if type[] is
     * detected in return value doc comment.
     *
     * @throws InvalidArgumentException
     *
     * @return string tns:xsd-type
     */
    #[Override()]
    public function addComplexType(string $type): string
    {
        if (($soapType = $this->scanRegisteredTypes($type)) !== null) {
            return $soapType;
        }

        $singularType = $this->getSingularPhpType($type);
        $nestingLevel = $this->getNestedCount($type);

        if ($nestingLevel === 0) {
            return parent::addComplexType($singularType);
        }

        throw_if($nestingLevel !== 1, InvalidArgumentException::class, 'ArrayOfTypeComplex cannot return nested ArrayOfObject deeper than one level. '
        .'Use array object properties to return deep nested data.');

        // The following blocks define the Array of Object structure
        return $this->addArrayOfComplexType($singularType, $type);
    }

    /**
     * Add an ArrayOfType based on the xsd:complexType syntax if type[] is
     * detected in return value doc comment.
     *
     * @param string $singularType e.g. '\MyNamespace\MyClassname'
     * @param string $type         e.g. '\MyNamespace\MyClassname[]'
     *
     * @return string tns:xsd-type   e.g. 'tns:ArrayOfMyNamespace.MyClassname'
     */
    protected function addArrayOfComplexType(string $singularType, string $type): string
    {
        if (($soapType = $this->scanRegisteredTypes($type)) !== null) {
            return $soapType;
        }

        $xsdComplexTypeName = 'ArrayOf'.$this->requireContext()->translateType($singularType);
        $xsdComplexType = Wsdl::TYPES_NS.':'.$xsdComplexTypeName;

        // Register type here to avoid recursion
        $this->requireContext()->addType($type, $xsdComplexType);

        // Process singular type using DefaultComplexType strategy
        parent::addComplexType($singularType);

        // Add array type structure to WSDL document
        $dom = $this->requireContext()->toDomDocument();

        $complexType = $dom->createElementNS(Wsdl::XSD_NS_URI, 'complexType');
        $this->requireContext()->getSchema()->appendChild($complexType);

        $complexType->setAttribute('name', $xsdComplexTypeName);

        $complexContent = $dom->createElementNS(Wsdl::XSD_NS_URI, 'complexContent');
        $complexType->appendChild($complexContent);

        $xsdRestriction = $dom->createElementNS(Wsdl::XSD_NS_URI, 'restriction');
        $complexContent->appendChild($xsdRestriction);
        $xsdRestriction->setAttribute('base', Wsdl::SOAP_ENC_NS.':Array');

        $xsdAttribute = $dom->createElementNS(Wsdl::XSD_NS_URI, 'attribute');
        $xsdRestriction->appendChild($xsdAttribute);

        $xsdAttribute->setAttribute('ref', Wsdl::SOAP_ENC_NS.':arrayType');
        $xsdAttribute->setAttributeNS(
            Wsdl::WSDL_NS_URI,
            'arrayType',
            Wsdl::TYPES_NS.':'.$this->requireContext()->translateType($singularType).'[]',
        );

        return $xsdComplexType;
    }

    /**
     * From a nested definition with type[] or array<Type>, get the singular PHP Type
     */
    private function getSingularPhpType(string $type): string
    {
        // Handle array<Type> notation
        if (preg_match('/^array<(.+)>$/i', $type, $matches)) {
            return $matches[1];
        }

        // Handle Type[] notation
        return str_replace('[]', '', $type);
    }

    /**
     * Return the array nesting level based on the type name
     */
    private function getNestedCount(string $type): int
    {
        // Handle array<Type> notation (always 1 level)
        if (preg_match('/^array<.+>$/i', $type)) {
            return 1;
        }

        // Handle Type[] notation
        return mb_substr_count($type, '[]');
    }
}
