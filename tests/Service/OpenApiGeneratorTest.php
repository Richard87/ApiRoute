<?php

namespace Richard87\ApiRoute\Tests\Service;

use PHPUnit\Framework\TestCase;
use Richard87\ApiRoute\Service\ApiRouteLoader;
use Richard87\ApiRoute\Service\FindClassDescriptors;
use Richard87\ApiRoute\Service\OpenApiGenerator;
use Symfony\Component\Routing\RouterInterface;

class OpenApiGeneratorTest extends TestCase
{
    private OpenApiGenerator $openApi;

    protected function setUp(): void
    {
        $apiLoader = new ApiRouteLoader(new FindClassDescriptors());
        $routeCollection = $apiLoader->load(__DIR__ . "/../../src");
        $mockRouter = $this->createMock(RouterInterface::class);
        $mockRouter->method("getRouteCollection")->willReturn($routeCollection);

        $this->openApi = new OpenApiGenerator($mockRouter);
    }

    public function testOpenApiGenerator(): void {

        $schema = $this->openApi->getDefinition();

        self::assertNotEmpty($schema);
    }
}
