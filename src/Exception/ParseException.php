<?php


namespace App\Exception;


use Exception;

class ParseException extends Exception
{
    protected $code = 400;
}