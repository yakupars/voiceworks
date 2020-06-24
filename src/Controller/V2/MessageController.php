<?php

namespace App\Controller\V2;

use App\Contract\RequestDaoInterface;
use App\Contract\RequestProcessInterface;
use App\Contract\ResponseDaoInterface;
use App\Dao\Common\Error;
use App\Dao\NackResponse;
use App\Exception\ElementNotFoundException;
use App\Exception\FileNotFoundException;
use App\Exception\ParseException;
use App\Exception\SchemeValidationException;
use App\RequestProcess\RequestProcessFactory;
use App\Service\MessageProcessService;
use App\Service\XmlSerializer;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * This is more object oriented way to achieve the task.
 * This approach requires creating mappings for the request and response daos - services - processors
 * for any given request - response type
 *
 * This class provides single endpoint and uses services to process the request.
 * Any service used in this class is generic so it can be used for different type of requests and responses
 *
 * Class MessageController
 * @package App\Controller\V2
 */
class MessageController extends AbstractController
{
    /**
     * @Route("/message", name="messagev2", methods={"POST"})
     *
     * @param Request $request
     * @param MessageProcessService $messageProcessService
     * @param XmlSerializer $xmlSerializer
     * @param ParameterBagInterface $parameterBag
     *
     * @return Response
     */
    public function message(Request $request, MessageProcessService $messageProcessService, XmlSerializer $xmlSerializer, ParameterBagInterface $parameterBag)
    {
        // this is optional parameter just for triggering fail response to see if it is working
        $testFail = $request->query->getInt('testfail', 0);

        // xml serializing options
        $context = [
            'xml_root_node_name' => 'nack',
            'xml_format_output' => false,
            'xml_encoding' => 'utf-8',
        ];

        // here we create the returning response to fill its content later
        $response = new Response();
        $response->headers->set('Content-Type', 'application/xml');

        // xml string of the request body
        $requestContent = $request->getContent();

        // here we try to parse request body. if fails than return nack response immediately
        try {
            $messageProcessService->parse($requestContent);
        } catch (ParseException $e) {
            return $this->prepareNackResponse($response, $e, $xmlSerializer, $context);
        }

        // here we try to validate the request body with paired scheme. if fails than return nack response immediately
        try {
            $messageProcessService->validateWithSchema($requestContent);
        } catch (ElementNotFoundException | FileNotFoundException | SchemeValidationException $e) {
            return $this->prepareNackResponse($response, $e, $xmlSerializer, $context);
        }

        // here we try to decode xml message to get the request type
        try {
            $decoded = $xmlSerializer->decode($requestContent, 'xml', $context);
        } catch (Exception $exception) {
            $error = new Error();
            $error
                ->setCode(400)
                ->setMessage('Decoding request failed.');

            $nackResponse = new NackResponse();
            $nackResponse->getBody()->error = $error;

            $response->setStatusCode(400);
            $response->setContent($xmlSerializer->serialize($nackResponse, 'xml', $context));

            return $response;
        }

        /**
         * this checks if we add the needed configuration
         * about the request and paired response
         * to the project configurations located in config/daomap.yaml
         *
         * ex: parameters:
         *         nack: App\Dao\NackResponse
         *         ping_request:
         *             processor: App\RequestProcess\PingRequestProcessor
         *             class: App\Dao\PingRequest
         *             response:
         *             type: ping_response
         *             class: App\Dao\PingResponse
         */
        if (!$parameterBag->has($decoded['header']['type'])) {
            $error = new Error();
            $error
                ->setCode(500)
                ->setMessage('Project does not know about this request type yet!');

            $nackResponse = new NackResponse();
            $nackResponse->getBody()->error = $error;

            $response->setStatusCode(500);
            $response->setContent($xmlSerializer->serialize($nackResponse, 'xml', $context));

            return $response;
        }

        // creating object with found request type
        $requestClass = $parameterBag->get($decoded['header']['type'])['class'];
        /** @var RequestDaoInterface $requestDao */
        $requestDao = new $requestClass();

        // populate the $requestDao object with request content to later use
        $xmlSerializer->deserialize($requestContent, $requestClass, 'xml', array_merge($context, [AbstractNormalizer::OBJECT_TO_POPULATE => $requestDao]));

        // creating object with found response type
        $responseClass = $parameterBag->get($decoded['header']['type'])['response']['class'];
        /** @var ResponseDaoInterface $responseDao */
        $responseDao = new $responseClass();

        /**
         * this factory creates appropriate request processor to process the request and prepare response
         * which is where the business logic of the request is exists
         *
         * @var RequestProcessInterface $requestProcessor
         */
        $requestProcessor = RequestProcessFactory::create($parameterBag->get($decoded['header']['type'])['processor']);

        // filling data to response object with sent request
        $responseDao
            ->getHeader()
            ->setType($parameterBag->get($decoded['header']['type'])['response']['type'])
            ->setSender($requestDao->getHeader()->getRecipient())
            ->setRecipient($requestDao->getHeader()->getSender())
            ->setReference($requestDao->getHeader()->getReference());

        // setting appropriate root node name
        $context['xml_root_node_name'] = $responseDao->getHeader()->getType();

        // running business logic with created request processor and reaching final state of $responseDao
        $responseDao = $requestProcessor->process($requestDao, $responseDao, $testFail);

        // here we try to validate the response body with paired scheme. if fails than return nack response immediately
        try {
            $messageProcessService->validateWithSchema($xmlSerializer->serialize($responseDao, 'xml', $context));
        } catch (Exception $exception) {
            return $this->prepareNackResponse($response, $exception, $xmlSerializer, $context);
        }

        // here we are finally serializing the dao to create xml string
        $responseContent = $xmlSerializer->serialize($responseDao, 'xml', array_merge($context, [
            'xml_root_node_name' => $responseDao->getHeader()->getType(),
        ]));

        // if response has error element in its body then use the code element to set http response status code
        if ($responseDao->getBody()->getError())
            $response->setStatusCode($responseDao->getBody()->getError()->getCode());

        // set response body here
        $response->setContent($responseContent);

        // done with the endpoint - request
        return $response;
    }

    /**
     * This is a helper method to populate the nack response.
     * We can use this method to reduce line count and to manage error response in one place
     *
     * @param Response $response
     * @param Exception $exception
     * @param XmlSerializer $xmlSerializer
     * @param array $context
     * @return Response
     */
    private function prepareNackResponse(Response $response, Exception $exception, XmlSerializer $xmlSerializer, array $context): Response
    {
        // creation of error dao
        $error = new Error();
        $error
            ->setCode($exception->getCode())
            ->setMessage($exception->getMessage());

        // creation of nack response
        $nackResponse = new NackResponse();
        $nackResponse->getBody()->error = $error;

        // http response status code and body set here
        $response->setStatusCode($exception->getCode());
        $response->setContent($xmlSerializer->serialize($nackResponse, 'xml', $context));

        // return http response
        return $response;
    }
}