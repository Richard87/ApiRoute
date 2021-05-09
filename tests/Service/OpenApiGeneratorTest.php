<?php

namespace Richard87\ApiRoute\Tests\Service;

use PHPUnit\Framework\TestCase;
use Richard87\ApiRoute\Service\ApiRouteLoader;
use Richard87\ApiRoute\Service\FindClassDescriptors;
use Richard87\ApiRoute\Service\OpenApiGenerator;
use Richard87\ApiRoute\Tests\TestKernel;
use Symfony\Component\Routing\RouterInterface;

class OpenApiGeneratorTest extends TestCase
{
    private OpenApiGenerator $openApi;

    protected function setUp(): void
    {
        $kernel = new TestKernel();
        $kernel->boot();
        $container = $kernel->getContainer();
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->openApi = $container->get("api_route.openapi_generator");
    }

    /**
     * @covers OpenApiGenerator::class
     */
    public function testOpenApiGenerator(): void {

        $schema = $this->openApi->getDefinition();

        echo json_encode($schema);

        self::assertNotEmpty($schema);
    }
}
