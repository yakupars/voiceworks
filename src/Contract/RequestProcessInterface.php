<?php


namespace App\Contract;


interface RequestProcessInterface
{
    public function process(RequestDaoInterface $requestDao, ResponseDaoInterface $responseDao, ?int $testFail): ResponseDaoInterface;
}