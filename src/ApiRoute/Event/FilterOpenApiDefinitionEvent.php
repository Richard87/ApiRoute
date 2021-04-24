<?php


namespace Richard87\ApiRoute\Event;


class FilterOpenApiDefinitionEvent
{
    public function __construct(
        private array $json,
    ){}

    public function getJson(): array
    {
        return $this->json;
    }

    public function setJson(array $json): void
    {
        $this->json = $json;
    }
}