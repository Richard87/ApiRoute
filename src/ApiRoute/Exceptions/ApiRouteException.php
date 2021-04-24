<?php


namespace Richard87\ApiRoute\Exceptions;



class ApiRouteException extends ApiException
{
    public function __construct()
    {
        parent::__construct("Something is wrong with API-Tools! Please report it as soon as possible for fix!");
    }
}