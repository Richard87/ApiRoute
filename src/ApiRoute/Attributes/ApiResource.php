<?php


namespace Richard87\ApiRoute\Attributes;


use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
/**
 * Tells ApiRoute that the object have it's own identifier.
 */
class ApiResource
{
    public function __construct(
        public ?string $path = null,
        public ?string $name = null,
        public ?string $summary = null,
        public string $identifier = "id",
    ){}
}