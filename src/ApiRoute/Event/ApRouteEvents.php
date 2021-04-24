<?php declare(strict_types=1);

namespace Richard87\ApiRoute\Event;

use \Richard87\ApiRoute\Event\FilterOpenApiDefinitionEvent;

class ApRouteEvents
{
    /**
     * Modify the OpenAPI Definition api before returning it to the user
     *
     * @Event(FilterOpenApiDefinitionEvent::class)
     */
    public const FILTER_OPENAPI_DEFINTION = "api_route.open_api_definition_event";
}