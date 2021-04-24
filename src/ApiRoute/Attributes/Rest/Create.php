<?php


namespace Richard87\ApiRoute\Attributes\Rest;

use Richard87\ApiRoute\Attributes\ApiRoute;
use Richard87\ApiRoute\Controller\RestActions\CreateAction;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Create extends ApiRoute
{
    public function __construct(
        public ?string $input = null,
        public ?string $controller = null,
        public ?string $security = null,
    ){
        parent::__construct(
            input: $this->input,
            output: $this->controller ?? CreateAction::class,
            controller: $this->controller ?? CreateAction::class,
            path: "",
            security: $this->security,
        );
    }
}