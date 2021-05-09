<?php


namespace Richard87\ApiRoute\Attributes\Rest;

use Richard87\ApiRoute\Attributes\ApiResource;
use Richard87\ApiRoute\Controller\RestActions\GetAction;
use Richard87\ApiRoute\Attributes\ApiRoute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Get extends ApiRoute
{
    public function __construct(
        public ?string $controller = null,
        public ?string $security = null,
    ){
        parent::__construct(
            method: "GET",
            controller: $this->controller ?? GetAction::class,
            path: "",
            security: $this->security,
        );
    }

    public function withClass(\ReflectionClass $class, ?ApiResource $descriptor): void
    {
        $this->output = $class->getName();
        parent::withClass($class, $descriptor);
    }
}