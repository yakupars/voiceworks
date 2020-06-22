<?php


namespace App\Exception;


use Exception;

class FileNotFoundException extends Exception
{
    protected $code = 404;
}