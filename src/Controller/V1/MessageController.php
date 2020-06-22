<?php

namespace App\Controller\V1;

use App\Exception\FileNotFoundException;
use App\Exception\ParseException;
use App\Service\MessageProcessService;
use App\Service\ResponseService;
use App\Service\XsdToXmlConverterService;
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
            $nackResponseContent = $requestProcessService->nack($responseStructure, $e->getMessage());

            $response->setStatusCode($e->getCode());
            $response->setContent($nackResponseContent);

            return $response;
        }

        try {
            $messageProcessService->validateWithSchema($requestContent);
            $requestType = $messageProcessService->extractType($requestContent);
        } catch (Exception $e) {
            $xsdString = $messageProcessService->getXsdByType('nack');
            $responseStructure = $xsdToXmlConverterService->populateByXsd($xsdString);
            $nackResponseContent = $requestProcessService->nack($responseStructure, $e->getMessage());

            $response->setStatusCode($e->getCode());
            $response->setContent($nackResponseContent);

            return $response;
        }

        $responseType = str_replace('request', 'response', $requestType);
        $xsdString = $messageProcessService->getXsdByType($responseType);
        $responseStructure = $xsdToXmlConverterService->populateByXsd($xsdString);

        try {
            $responseContent = $requestProcessService->{$responseType}($responseStructure, $requestDomDocument);
        } catch (Throwable $e) {
            $xsdString = $messageProcessService->getXsdByType('nack');
            $responseStructure = $xsdToXmlConverterService->populateByXsd($xsdString);
            $responseContent = $requestProcessService->nack($responseStructure, $e->getMessage());

            $response->setStatusCode($e->getCode());
            $response->setContent($responseContent);

            return $response;
        }

        try {
            $messageProcessService->load($responseContent);
            $messageProcessService->validateWithSchema($responseContent);
        } catch (Exception $e) {
            $xsdString = $messageProcessService->getXsdByType('nack');
            $responseStructure = $xsdToXmlConverterService->populateByXsd($xsdString);
            $nackResponseContent = $requestProcessService->nack($responseStructure, $e->getMessage());

            $response->setStatusCode($e->getCode());
            $response->setContent($nackResponseContent);

            return $response;
        }

        $response->setContent($responseContent);

        return $response;
    }
}
