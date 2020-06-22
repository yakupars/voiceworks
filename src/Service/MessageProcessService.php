<?php


namespace App\Service;


use App\Exception\ElementNotFoundException;
use App\Exception\FileNotFoundException;
use App\Exception\ParseException;
use App\Exception\SchemeValidationException;
use DOMDocument;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\SerializerInterface;

class MessageProcessService
{
    private SerializerInterface $serializer;
    private ParameterBagInterface $parameterBag;

    public DOMDocument $xmlMessage;

    public function __construct(
        XmlSerializer $serializer,
        ParameterBagInterface $parameterBag
    )
    {
        $this->serializer = $serializer;
        $this->parameterBag = $parameterBag;
    }

    public function serialize($data, $rootNode = 'nack'): string
    {
        return $this->serializer->serialize($data, 'xml', [XmlEncoder::ENCODING => 'utf-8', XmlEncoder::ROOT_NODE_NAME => $rootNode]);
    }

    /**
     * @param string $xml
     *
     * @return DOMDocument
     *
     * @throws ParseException
     */
    public function load(string $xml)
    {
        $xmlMessage = new DOMDocument();
        $xmlMessage->preserveWhiteSpace = false;

        try {
            $xmlMessage->loadXML($xml);
        } catch (Exception $exception) {
            throw new ParseException('Can not parse the requested xml. ' . $exception->getMessage());
        }

        return $this->xmlMessage = $xmlMessage;
    }

    /**
     * @param string $xml
     *
     * @return string
     *
     * @throws ElementNotFoundException
     */
    public function extractType(string $xml): string
    {
        $decoded = $this->serializer->decode($xml, 'xml');

        if (!array_key_exists('header', $decoded)) {
            throw new ElementNotFoundException('header element not found');
        }

        if (!array_key_exists('type', $decoded['header'])) {
            throw new ElementNotFoundException('type element not found in header element');
        }

        return $decoded['header']['type'];
    }

    /**
     * @param string $type
     *
     * @return string
     *
     * @throws FileNotFoundException
     */
    public function getXsdByType(string $type)
    {
        $xsdDir = $this->parameterBag->get('xsd.dir');
        $finder = new Finder();
        $finder->files()->in($xsdDir)->name($type . '.xsd');

        if (!$finder->hasResults()) {
            throw new FileNotFoundException($type . '.xsd not found in ' . $xsdDir);
        }

        foreach ($finder as $splFileInfo) {
            return $splFileInfo->getContents();
        }
    }

    /**
     * @param string $xml
     *
     * @return string
     *
     * @throws ElementNotFoundException
     * @throws FileNotFoundException
     */
    public function getXsdByXml(string $xml): string
    {
        $messageType = $this->extractType($xml);

        return $this->getXsdByType($messageType);
    }

    /**
     * @param string $xml
     *
     * @throws ElementNotFoundException
     * @throws FileNotFoundException
     * @throws SchemeValidationException
     */
    public function validateWithSchema(string $xml)
    {
        $xsdContent = $this->getXsdByXml($xml);

        try {
            $this->xmlMessage->schemaValidateSource($xsdContent);
        } catch (Exception $e) {
            $newMessage = strstr($e->getMessage(), 'Element');

            throw new SchemeValidationException($newMessage);
        }
    }
}