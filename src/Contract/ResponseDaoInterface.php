<?php


namespace App\Contract;


use App\Dao\Common\Header;

interface ResponseDaoInterface
{
    public function getHeader(): Header;

    public function setHeader(Header $header): ResponseDaoInterface;
}