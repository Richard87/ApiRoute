<?php declare(strict_types=1);


namespace Richard87\ApiRoute\Tests\resources\src\Controller;


use Symfony\Component\HttpFoundation\Response;

class InviteController
{
    public function __invoke(): \DateTime
    {
        return new \DateTime();
    }
}