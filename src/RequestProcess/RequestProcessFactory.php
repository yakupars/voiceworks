<?php


namespace App\RequestProcess;


class RequestProcessFactory
{
    public static function create(string $class)
    {
        return new $class();
    }
}