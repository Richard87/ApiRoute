<?php


namespace Richard87\ApiRoute\Controller\RestActions;


class GetAction
{
    public function __invoke(object $entity): object
    {
        return $entity;
    }
}