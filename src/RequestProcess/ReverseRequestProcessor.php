<?php


namespace App\RequestProcess;


use App\Contract\RequestDaoInterface;
use App\Contract\RequestProcessInterface;
use App\Contract\ResponseDaoInterface;
use App\Dao\Common\Error;

class ReverseRequestProcessor implements RequestProcessInterface
{
    public function process(RequestDaoInterface $requestDao, ResponseDaoInterface $responseDao, ?int $testFail): ResponseDaoInterface
    {
        $someBusinessLogicDoneHereAndSomeErrorOccurred = !!$testFail;

        if ($someBusinessLogicDoneHereAndSomeErrorOccurred) {
            $error = new Error();
            $error
                ->setCode(400)
                ->setMessage('Reverse request failed.');

            $responseDao->getBody()
                ->setString($requestDao->getBody()->getString())
                ->setError($error);

            return $responseDao;
        }

        $responseDao->getBody()
            ->setString($requestDao->getBody()->getString())
            ->setReverse(strrev($requestDao->getBody()->getString()));

        return $responseDao;
    }
}