<?php


namespace Richard87\ApiRoute\Attributes;


use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Description
{
    public function __construct(
        public ?string $path = null,
        public ?string $name = null,
        public ?string $summary = null,
    ){}
}