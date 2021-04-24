<?php


namespace Richard87\ApiRoute\Attributes;


use Richard87\ApiRoute\Controller\RestActions\CollectionAction as RestCollectionAction;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY)]
class CollectionRoute extends ApiRoute
{
    public function __construct(
        public ?string $output = null,
        public ?string $controller = null,

        public ?string $path = null,
        public ?string $security = null,
        public ?string $pathName = null,
    ){
        parent::__construct(
            method: "GET",
            input: $this->output,
            output: $this->output,
            controller: $this->controller ?? RestCollectionAction::class,
            path: $this->path,
            security: $this->security,
            pathName: $this->pathName,
            isItemOperation: false,
        );
    }
}