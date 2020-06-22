<?php


namespace App\Exception;


use Exception;

class ElementNotFoundException extends Exception
{
    protected $code = 404;
}