<?php


namespace Richard87\ApiRoute\Tests\resources\src\Controller;


use Richard87\ApiRoute\Tests\resources\Entity\User;

class FetchImportantMessagesController
{
    public function __invoke(User $user): array
    {
        return [];
    }
}