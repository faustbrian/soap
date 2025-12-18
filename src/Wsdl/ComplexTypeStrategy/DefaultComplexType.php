<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Wsdl\ComplexTypeStrategy;

use Cline\Soap\Exception;
use Cline\Soap\Exception\InvalidArgumentException;
use Cline\Soap\Wsdl;
use Cline\Soap\Wsdl\DocumentationStrategy\DocumentationStrategyInterface;
use DOMElement;
use ReflectionClass;
use ReflectionProperty;

use function class_exists;
use function mb_trim;
use function preg_match_all;
use function sprintf;

final class DefaultComplexType extends AbstractComplexTypeStrategy
{
    /**
     * Add a complex type by recursively using all the class properties fetched via Reflection.
     *
     * @param  string                   $type Name of the class to be specified
     * @throws InvalidArgumentException If class does not exist.
     * @return string                   XSD Type for the given PHP type
     */
    public function addComplexType($type)
    {
        if (!class_exists($type)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot add a complex type %s that is not an object or where '
                .'class could not be found in "DefaultComplexType" strategy.',
                $type,
            ));
        }

        $class = new ReflectionClass($type);
        $phpType = $class->getName();

        if (($soapType = $this->scanRegisteredTypes($phpType)) !== null) {
            return $soapType;
        }

        $dom = $this->getContext()->toDomDocument();
        $soapTypeName = $this->getContext()->translateType($phpType);
        $soapType = Wsdl::TYPES_NS.':'.$soapTypeName;

        // Register type here to avoid recursion
        $this->getContext()->addType($phpType, $soapType);

        $defaultProperties = $class->getDefaultProperties();

        $complexType = $dom->createElementNS(Wsdl::XSD_NS_URI, 'complexType');
        $complexType->setAttribute('name', $soapTypeName);

        $all = $dom->createElementNS(Wsdl::XSD_NS_URI, 'all');

        foreach ($class->getProperties() as $property) {
            if (!$property->isPublic() || !preg_match_all('/@var\s+([^\s]+)/m', $property->getDocComment(), $matches)) {
                continue;
            }

            /**
             * @todo check if 'xsd:element' must be used here (it may not be
             * compatible with using 'complexType' node for describing other
             * classes used as attribute types for current class
             */
            $element = $dom->createElementNS(Wsdl::XSD_NS_URI, 'element');
            $element->setAttribute('name', $propertyName = $property->getName());
            $element->setAttribute('type', $this->getContext()->getType(mb_trim($matches[1][0])));

            // If the default value is null, then this property is nillable.
            if ($defaultProperties[$propertyName] === null) {
                $element->setAttribute('nillable', 'true');
            }

            $this->addPropertyDocumentation($property, $element);
            $all->appendChild($element);
        }

        $complexType->appendChild($all);
        $this->addComplexTypeDocumentation($class, $complexType);
        $this->getContext()->getSchema()->appendChild($complexType);

        return $soapType;
    }

    private function addPropertyDocumentation(ReflectionProperty $property, DOMElement $element): void
    {
        if (!$this->documentationStrategy instanceof DocumentationStrategyInterface) {
            return;
        }

        $documentation = $this->documentationStrategy->getPropertyDocumentation($property);

        if (!$documentation) {
            return;
        }

        $this->getContext()->addDocumentation($element, $documentation);
    }

    private function addComplexTypeDocumentation(ReflectionClass $class, DOMElement $element): void
    {
        if (!$this->documentationStrategy instanceof DocumentationStrategyInterface) {
            return;
        }

        $documentation = $this->documentationStrategy->getComplexTypeDocumentation($class);

        if (!$documentation) {
            return;
        }

        $this->getContext()->addDocumentation($element, $documentation);
    }
}
