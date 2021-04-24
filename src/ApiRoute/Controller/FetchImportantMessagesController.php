<?php


namespace Richard87\ApiRoute\Controller;


use Richard87\ApiRoute\Entity\User;

class FetchImportantMessagesController
{
    public function __invoke(User $user): array
    {
        return [];
    }
}