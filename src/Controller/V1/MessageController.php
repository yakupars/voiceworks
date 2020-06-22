<?php

namespace App\Controller\V1;

use App\Exception\FileNotFoundException;
use App\Exception\ParseException;
use App\Service\MessageProcessService;
use App\Service\ResponseService;
use App\Service\XsdToXmlConverterService;
use DOMDocument;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class MessageController extends AbstractController
{
    /**
     * @Route("/message", name="message", methods={"POST"})
     *
     * @param Request $request
     * @param MessageProcessService $messageProcessService
     * @param XsdToXmlConverterService $xsdToXmlConverterService
     * @param ResponseService $requestProcessService
     *
     * @return Response
     * @throws FileNotFoundException
     */
    public function message(
        Request $request,
        MessageProcessService $messageProcessService,
        XsdToXmlConverterService $xsdToXmlConverterService,
        ResponseService $requestProcessService
    )
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/xml');

        $requestContent = $request->getContent();

        try {
            $requestDomDocument = $messageProcessService->load($requestContent);
        } catch (Exception $e) {
            $xsdString = $messageProcessService->getXsdByType('nack');
            $responseStructure = $xsdToXmlConverterService->populateByXsd($xsdString);
            $nackResponseDom = $requestProcessService->nack($responseStructure, $e->getMessage());

            $response->setStatusCode($e->getCode());
            $response->setContent($nackResponseDom->saveXml());

            return $response;
        }

        try {
            $messageProcessService->validateWithSchema($requestContent);
            $requestType = $messageProcessService->extractType($requestContent);
        } catch (Exception $e) {
            $xsdString = $messageProcessService->getXsdByType('nack');
            $responseStructure = $xsdToXmlConverterService->populateByXsd($xsdString);
            $nackResponseDom = $requestProcessService->nack($responseStructure, $e->getMessage());

            $response->setStatusCode($e->getCode());
            $response->setContent($nackResponseDom->saveXml());

            return $response;
        }

        $responseType = str_replace('request', 'response', $requestType);
        $xsdString = $messageProcessService->getXsdByType($responseType);
        $responseStructure = $xsdToXmlConverterService->populateByXsd($xsdString);

        try {
            /** @var DOMDocument $responseDom */
            $responseDom = $requestProcessService->{$responseType}($responseStructure, $requestDomDocument);
        } catch (Throwable $e) {
            $xsdString = $messageProcessService->getXsdByType('nack');
            $responseStructure = $xsdToXmlConverterService->populateByXsd($xsdString);
            $responseDom = $requestProcessService->nack($responseStructure, $e->getMessage());

            $response->setStatusCode($e->getCode());
            $response->setContent($responseDom->saveXml());

            return $response;
        }

        try {
            $messageProcessService->load($responseDom->saveXML());
            $messageProcessService->validateWithSchema($responseDom->saveXML());
        } catch (Exception $e) {
            $xsdString = $messageProcessService->getXsdByType('nack');
            $responseStructure = $xsdToXmlConverterService->populateByXsd($xsdString);
            $nackResponseDom = $requestProcessService->nack($responseStructure, $e->getMessage());

            $response->setStatusCode($e->getCode());
            $response->setContent($nackResponseDom->saveXml());

            return $response;
        }

        if ($responseDom->firstChild->lastChild->lastChild->nodeName == 'error') {
            $response->setStatusCode($responseDom->firstChild->lastChild->lastChild->firstChild->nodeValue);
        }

        $response->setContent($responseDom->saveXml());

        return $response;
    }
}
