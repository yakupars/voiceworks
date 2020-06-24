<?php


namespace App\Service;


use DOMAttr;
use DOMDocument;
use DOMNode;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * This class - service is used to process xml scheme definition file to create a signature array of xml itself
 *
 * Class XsdToXmlConverterService
 * @package App\Service
 */
class XsdToXmlConverterService
{
    private ParameterBagInterface $parameterBag;
    private string $prefix;

    private array $elements = [];

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * used for parsing elements of xsd file
     *
     * @param string $xsd
     * @return array
     */
    public function populateByXsd(string $xsd)
    {
        $xsdDom = new DOMDocument();
        $xsdDom->preserveWhiteSpace = false;

        $xsdDom->loadXML($xsd);

        $this->analyzeNode($xsdDom->firstChild);

        return $this->elements;
    }

    /**
     * use for analyzing the xsd content step by step recursively
     *
     * @param DOMNode $node
     */
    public function analyzeNode(DOMNode $node)
    {
        if ($node->localName === 'schema') {
            $this->prefix = $DOMElement->prefix ?? 'xs';
        }

        if ($node->localName === 'element') {
            $this->element($node);
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $childNode) {
                $this->analyzeNode($childNode);
            }
        }
    }

    /**
     * used for analyzing the xsd element attributes
     *
     * @param DOMNode $node
     * @return array
     */
    public function analyzeAttrs(DOMNode $node)
    {
        $attrs = [];

        /** @var DOMAttr $attribute */
        foreach ($node->attributes as $attribute) {
            $attrs[$attribute->name] = str_replace($this->prefix . ':', '', $attribute->value);
        }

        return $attrs;
    }

    /**
     * used for finding the parent element of the given node
     * works inside out recursively until it finds the parent element
     *
     * @param DOMNode|null $node
     * @return string|null
     */
    public function findParentElement(?DOMNode $node)
    {
        if (is_null($node)) {
            return null;
        }

        if ($node->localName === 'complexType') {
            return $node->attributes->getNamedItem('name')->nodeValue;
        } else {
            return $this->findParentElement($node->parentNode);
        }
    }

    /**
     * used for finding properties for simpletype element
     * works recursively outside in
     *
     * @param DOMNode|null $node
     * @param $properties
     * @return mixed
     */
    public function findChildProperties(?DOMNode $node, &$properties)
    {
        $attrs = $this->analyzeAttrs($node);

        if ($node->localName === 'restriction') {
            $properties['type'] = str_replace($this->prefix . ':', '', $attrs['base']);
        }

        if ($node->localName === 'maxLength') {
            $properties['maxLength'] = $attrs['value'];
        }

        if ($node->localName === 'pattern') {
            $properties['pattern'] = $attrs['value'];
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $childNode) {
                return $this->findChildProperties($childNode, $properties);
            }
        }

        return $properties;
    }

    /**
     * processing the element node
     *
     * @param DOMNode $node
     */
    public function element(DOMNode $node)
    {
        $attrs = $this->analyzeAttrs($node);

        $info = array_merge($attrs, [
            'parentType' => !is_null($node->parentNode) ? $this->findParentElement($node) : null,
        ]);

        $properties = [];
        if ($node->hasChildNodes()) {
            $properties = $this->findChildProperties($node->firstChild, $properties);
        }

        $this->elements[$attrs['name']] = array_merge($info, $properties);
    }
}