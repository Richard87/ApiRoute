<?php

namespace Richard87\ApiRoute\Controller;

use Richard87\ApiRoute\Event\FilterOpenApiDefinitionEvent;
use Richard87\ApiRoute\Service\OpenApiGenerator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OpenApiController
{
    public function __construct(
        private OpenApiGenerator $openApiGenerator,
        private ?EventDispatcherInterface $eventDispatcher = null,
    ){}


    public function __invoke(): Response
    {
        $definition = $this->openApiGenerator->getDefinition();
        $event = new FilterOpenApiDefinitionEvent($definition);

        if ($this->eventDispatcher)
            $this->eventDispatcher->dispatch($event);

        return new JsonResponse($event->getJson());
    }
}
