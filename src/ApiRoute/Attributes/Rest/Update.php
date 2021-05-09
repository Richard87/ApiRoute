<?php


namespace Richard87\ApiRoute\Attributes\Rest;


use Richard87\ApiRoute\Attributes\ApiResource;
use Richard87\ApiRoute\Controller\RestActions\UpdateAction;
use Richard87\ApiRoute\Attributes\ApiRoute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Update extends ApiRoute
{
    public function __construct(
        public ?string $input = null,
        public ?string $controller = null,
        public ?string $security = null,
    ){
        parent::__construct(
            method: "PATCH",
            input: $this->input,
            output: $this->controller ?? UpdateAction::class,
            controller: $this->controller ?? UpdateAction::class,
            path: "",
            security: $this->security
        );
    }

    public function withClass(\ReflectionClass $class, ?ApiResource $descriptor): void
    {
        $this->output = $class->getName();
        if (!$this->input)
            $this->input = $class->getName();

        parent::withClass($class, $descriptor);
    }
}