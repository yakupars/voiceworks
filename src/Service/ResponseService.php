<?php


namespace App\Service;


use DateTimeInterface;
use DOMDocument;
use DOMElement;

class ResponseService
{
    public static array $primitiveDataTypes = [
        'anyURI',
        'base64Binary',
        'boolean',
        'date',
        'dateTime',
        'decimal',
        'double',
        'duration',
        'float',
        'hexBinary',
        'gDay',
        'gMonth',
        'gMonthDay',
        'gYear',
        'gYearMonth',
        'NOTATION',
        'QName',
        'string',
        'time',
    ];

    public function nack(array $responseStructure, string $nackMessage)
    {
        $nackResponseDom = new DOMDocument('1.0', 'utf-8');
        foreach ($responseStructure as $element) {
            if (is_null($element['parentType'])) {
                $nackResponseDom->appendChild(new DOMElement($element['name']));
            } else {
                $tagName = $responseStructure[array_search($element['parentType'], array_column($responseStructure, 'type', 'name'))]['name'];

                switch ($element['name']) {
                    case 'code':
                        $newElement = new DOMElement($element['name'], 500);
                        break;
                    case 'message':
                        $newElement = new DOMElement($element['name'], $nackMessage);
                        break;
                    default:
                        $newElement = new DOMElement($element['name']);
                        break;
                }

                $nackResponseDom->getElementsByTagName($tagName)->item(0)->appendChild($newElement);
            }
        }

        return $nackResponseDom;
    }

    public function fillData(array $responseStructure, array $data, DOMDocument $responseDom)
    {
        foreach ($responseStructure as $element) {

            if (is_null($element['parentType'])) {
                $responseDom->appendChild(new DOMElement($element['name']));
                continue;
            }

            $tagName = $responseStructure[array_search($element['parentType'], array_column($responseStructure, 'type', 'name'))]['name'];

            if (in_array($element['type'], self::$primitiveDataTypes) && array_key_exists($element['name'], $data)) {
                $newElement = new DOMElement($element['name'], $data[$element['name']]);
                $responseDom->getElementsByTagName($tagName)->item(0)->appendChild($newElement);
            }
            if (!in_array($element['type'], self::$primitiveDataTypes) && !is_null($responseDom->getElementsByTagName($tagName)->item(0))) {
                $newElement = new DOMElement($element['name']);
                $responseDom->getElementsByTagName($tagName)->item(0)->appendChild($newElement);
            }
        }

        return $responseDom;
    }

    public function ping_response(array $responseStructure, DOMDocument $requestDom)
    {
        $data = [
            'type' => __FUNCTION__,
            'timestamp' => gmdate(DateTimeInterface::ATOM, time()),
            'sender' => $requestDom->getElementsByTagName('recipient')->item(0)->nodeValue,
            'recipient' => $requestDom->getElementsByTagName('sender')->item(0)->nodeValue,
            'reference' => $requestDom->getElementsByTagName('reference')->item(0)->nodeValue,
        ];

        if (strlen($requestDom->getElementsByTagName('reference')->item(0)->nodeValue) > $responseStructure['reference']['maxLength']) {
            $data['message'] = 'Max value exceeded for reference.';
            $data['code'] = '400';
            unset($responseStructure['echo']);
        } else {
            $data['echo'] = !is_null($requestDom->getElementsByTagName('echo')->item(0)) ? $requestDom->getElementsByTagName('echo')->item(0)->nodeValue : '';
            unset($responseStructure['error']);
            unset($responseStructure['message']);
            unset($responseStructure['code']);
        }

        $pingResponseDom = new DOMDocument('1.0', 'utf-8');
        $pingResponseDom->preserveWhiteSpace = false;

        $pingResponseDom = $this->fillData($responseStructure, $data, $pingResponseDom);

        return $pingResponseDom;
    }

    public function reverse_response(array $responseStructure, DOMDocument $requestDom)
    {
        $data = [
            'type' => __FUNCTION__,
            'timestamp' => gmdate(DateTimeInterface::ATOM, time()),
            'sender' => $requestDom->getElementsByTagName('recipient')->item(0)->nodeValue,
            'recipient' => $requestDom->getElementsByTagName('sender')->item(0)->nodeValue,
            'reference' => $requestDom->getElementsByTagName('reference')->item(0)->nodeValue,
            'string' => $requestDom->getElementsByTagName('string')->item(0)->nodeValue,
            'reverse' => strrev($requestDom->getElementsByTagName('string')->item(0)->nodeValue),
        ];

        if ($data['string']) {
            unset($responseStructure['error']);
            unset($responseStructure['message']);
            unset($responseStructure['code']);
        } else {
            $data['message'] = 'string element is empty';
            $data['code'] = '404';
        }

        $pingResponseDom = new DOMDocument('1.0', 'utf-8');
        $pingResponseDom->preserveWhiteSpace = false;

        $pingResponseDom = $this->fillData($responseStructure, $data, $pingResponseDom);

        return $pingResponseDom;
    }
}
