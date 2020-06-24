<?php


namespace App\RequestProcess;


use App\Contract\RequestDaoInterface;
use App\Contract\RequestProcessInterface;
use App\Contract\ResponseDaoInterface;
use App\Dao\Common\Error;

class PingRequestProcessor implements RequestProcessInterface
{
    public function process(RequestDaoInterface $requestDao, ResponseDaoInterface $responseDao, ?int $testFail): ResponseDaoInterface
    {
        $someBusinessLogicDoneHereAndSomeErrorOccurred = !!$testFail;

        if ($someBusinessLogicDoneHereAndSomeErrorOccurred) {
            $error = new Error();
            $error
                ->setCode(400)
                ->setMessage('Ping request failed.');

            $responseDao
                ->getBody()
                ->setError($error);

            return $responseDao;
        }

        $responseDao->getBody()
            ->setEcho($requestDao->getBody()->getEcho());

        return $responseDao;
    }
}