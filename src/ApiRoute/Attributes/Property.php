<?php


namespace Richard87\ApiRoute\Attributes;


use \Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Property
{
    public function __construct(
        public ?string $name = null,
        public string|bool $read = true,
        public string|bool $write = true,
    ){}
}