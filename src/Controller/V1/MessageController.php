<?php

namespace App\Controller\V1;

use App\Exception\FileNotFoundException;
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

/**
 * This is conventional way to achieve the task. It performs best with creating the xsd parsing library
 * and using it as a dependency
 *
 * This class provides single endpoint and uses services to process the request.
 * Any service used in this class is generic so it can be used for different type of requests and responses
 *
 * Class MessageController
 * @package App\Controller\V1
 */
class MessageController extends AbstractController
{
    /**
     * @Route("/message", name="message", methods={"POST"})
     *
     * @param Request $request
     * @param MessageProcessService $messageProcessService
     * @param XsdToXmlConverterService $xsdToXmlConverterService
     * @param ResponseService $responseService
     *
     * @return Response
     * @throws FileNotFoundException
     */
    public function message(
        Request $request,
        MessageProcessService $messageProcessService,
        XsdToXmlConverterService $xsdToXmlConverterService,
        ResponseService $responseService
    )
    {
        // here we create the returning response to fill its content later
        $response = new Response();
        $response->headers->set('Content-Type', 'application/xml');

        // xml string of the request body
        $requestContent = $request->getContent();

        // here we try to parse request body. if fails than return nack response immediately
        try {
            $requestDomDocument = $messageProcessService->parse($requestContent);
        } catch (Exception $e) {
            // get the xsd string by type
            $xsdString = $messageProcessService->getXsdByType('nack');
            // creating signature array of xsd file to create xml structure from it
            $responseStructure = $xsdToXmlConverterService->populateByXsd($xsdString);
            // nack response creation
            $nackResponseDom = $responseService->nack($responseStructure, $e->getMessage());

            $response->setStatusCode($e->getCode());
            $response->setContent($nackResponseDom->saveXml());

            return $response;
        }

        // here we try to validate the request body with paired scheme. if fails than return nack response immediately
        try {
            $messageProcessService->validateWithSchema($requestContent);
            $requestType = $messageProcessService->extractType($requestContent);
        } catch (Exception $e) {
            $xsdString = $messageProcessService->getXsdByType('nack');
            $responseStructure = $xsdToXmlConverterService->populateByXsd($xsdString);
            $nackResponseDom = $responseService->nack($responseStructure, $e->getMessage());

            $response->setStatusCode($e->getCode());
            $response->setContent($nackResponseDom->saveXml());

            return $response;
        }

        /**
         * we are assuming the file names for given request has the same suffix which is the specific to the request - response
         * if so we can parse the xsd file by finding the response_xxx.xsd by request type and create and signature array of that xsd file
         * to later use of creating xml itself
         */
        $responseType = str_replace('request', 'response', $requestType);
        $xsdString = $messageProcessService->getXsdByType($responseType);

        // this is where the signature array created by xsd file
        $responseStructure = $xsdToXmlConverterService->populateByXsd($xsdString);

        /**
         * here we are trying to process the request with its type. This is where the business logic lives.
         * @link ResponseService class has to have request spesific methods to process the request
         * if fails returns nack response immediately
         */
        try {
            /** @var DOMDocument $responseDom */
            $responseDom = $responseService->{$responseType}($responseStructure, $requestDomDocument);
        } catch (Throwable $e) {
            $xsdString = $messageProcessService->getXsdByType('nack');
            $responseStructure = $xsdToXmlConverterService->populateByXsd($xsdString);
            $responseDom = $responseService->nack($responseStructure, $e->getMessage());

            $response->setStatusCode($e->getCode());
            $response->setContent($responseDom->saveXml());

            return $response;
        }

        /**
         * optionally validate parse the response xml string.
         * this step is not because we already created or xml string by parsing its xsd rules
         */
        try {
            $messageProcessService->parse($responseDom->saveXML());
            $messageProcessService->validateWithSchema($responseDom->saveXML());
        } catch (Exception $e) {
            $xsdString = $messageProcessService->getXsdByType('nack');
            $responseStructure = $xsdToXmlConverterService->populateByXsd($xsdString);
            $nackResponseDom = $responseService->nack($responseStructure, $e->getMessage());

            $response->setStatusCode($e->getCode());
            $response->setContent($nackResponseDom->saveXml());

            return $response;
        }

        /**
         * if no exception thrown during execution of this api we can then read the status code
         * from error response dao
         * which processed in @link ResponseService
         * and use it to set http status code
         */
        if ($responseDom->firstChild->lastChild->lastChild->nodeName == 'error') {
            $response->setStatusCode($responseDom->firstChild->lastChild->lastChild->firstChild->nodeValue);
        }

        // set the response body
        $response->setContent($responseDom->saveXml());

        // done with the request - returning response
        return $response;
    }
}
