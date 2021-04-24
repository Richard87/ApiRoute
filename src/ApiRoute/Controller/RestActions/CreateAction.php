<?php


namespace Richard87\ApiRoute\Controller\RestActions;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateAction
{
    public function __invoke(Request $request): Response
    {
        return new Response();
    }
}