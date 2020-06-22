<?php


namespace App\Exception;


use Exception;

class SchemeValidationException extends Exception
{
    protected $code = 400;
}