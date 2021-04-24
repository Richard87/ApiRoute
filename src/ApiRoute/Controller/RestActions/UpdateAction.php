<?php


namespace Richard87\ApiRoute\Controller\RestActions;


use Symfony\Component\HttpFoundation\Response;

class UpdateAction
{
    public function __invoke(Request $request, object $entity): Response
    {
        return new Response();
    }
}