<?php


namespace Richard87\ApiRoute\Attributes\Rest;


use Richard87\ApiRoute\Attributes\ApiResource;
use Richard87\ApiRoute\Controller\RestActions\DeleteAction;
use Richard87\ApiRoute\Attributes\ApiRoute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Delete extends ApiRoute
{
    public function __construct(
        public ?string $controller = null,
        public ?string $security = null,
    ){
        parent::__construct(
            method: "DELETE",
            controller: $this->controller ?? DeleteAction::class,
            path: "",
            security: $this->security,
        );
        $this->isItemOperation = true;
    }
}