<?php


namespace Richard87\ApiRoute\Tests\resources\src\Controller;


use Symfony\Component\HttpFoundation\Response;

class IndexController
{
    public function indexAction(): Response {
        return new Response("Hello world!");
    }
}