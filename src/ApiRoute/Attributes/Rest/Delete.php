<?php


namespace Richard87\ApiRoute\Attributes\Rest;


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
    }

    public function getPath(string $basePath): string {
        $url = $basePath;
        if (str_ends_with($url, "/")) {
            $url = substr($url, 0, -1);
        }

        return "$url/{id}";
    }
}