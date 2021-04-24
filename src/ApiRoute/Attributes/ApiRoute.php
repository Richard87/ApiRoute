<?php


namespace Richard87\ApiRoute\Attributes;


use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ApiRoute
{
    public function __construct(
        public ?string $method = "POST",
        public ?string $input = null,
        public ?string $output = null,

        public ?string $controller = null,
        public ?string $path = null,
        public ?string $security = null,
        public ?string $pathName = null,
        public ?string $summary = null,
        public ?array $tags = null,
        public bool $isItemOperation = true,
    ){}
}