<?php


namespace Richard87\ApiRoute\Controller\RestActions;


class CollectionAction
{
    public function __invoke(object $entity = null): ?object
    {
        return $entity;
    }
}