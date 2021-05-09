<?php


namespace Richard87\ApiRoute\Tests\Service;


use PHPUnit\Framework\TestCase;
use Richard87\ApiRoute\Service\ApiRouteLoader;
use Richard87\ApiRoute\Tests\TestKernel;
use Symfony\Component\Routing\RouterInterface;

class ApiRouteLoaderTest extends TestCase
{

    /**
     * @throws \Exception
     * @covers ApiRouteLoader::class
     */
    public function testLoadSimpleRoutes() {
        $kernel = new TestKernel();
        $kernel->boot();
        $container = $kernel->getContainer();
        /** @var ApiRouteLoader $apiLoader */
        $apiLoader = $container->get("api_route.api_route_loader");
        $routes = $apiLoader->load("src");

        self::assertArrayHasKey("_api_route_openapi_endpoint", $routes->all());
        self::assertArrayHasKey("_api_route_swagger_endpoint",$routes->all());
        self::assertEquals(15, $routes->count());
    }
}