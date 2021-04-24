<?php


namespace Richard87\ApiRoute\Controller;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ActionController
{
    public function __invoke(Request $request): Response
    {
        return new Response(null, Response::HTTP_NOT_IMPLEMENTED);
    }
}