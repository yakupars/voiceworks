<?php


namespace App\Contract;


use App\Dao\Common\Header;

interface RequestDaoInterface
{
    public function getHeader(): Header;

    public function setHeader(Header $header): RequestDaoInterface;
}