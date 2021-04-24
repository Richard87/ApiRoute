<?php


namespace Richard87\ApiRoute\Attributes\Rest;

use Richard87\ApiRoute\Attributes\CollectionRoute;
use Richard87\ApiRoute\Controller\RestActions\CollectionAction as RestCollectionAction;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY)]
class Collection extends CollectionRoute
{
    public function __construct(
        public ?string $controller = null,
        public ?string $security = null,
    ){
        parent::__construct(
            controller:  $this->controller ?? RestCollectionAction::class,
            path: "",
            security:  $this->security,
        );
    }
}