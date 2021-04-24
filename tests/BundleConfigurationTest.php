<?php


use Richard87\ApiRoute\Service\OpenApiGenerator;
use Richard87\ApiRouteBundle\ApiRouteBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class BundleConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testServiceWiring(){
        $kernel = new TestKernel();
        $kernel->boot();
        $container = $kernel->getContainer();

        $service = $container->get("api_route.openapi_generator");
        self::assertInstanceOf(OpenApiGenerator::class, $service);
    }

    public function testServiceWiringWithConfig() {
        $kernel = new TestKernel([
            "openapi" => [
                "version" => "9.9.9",
                "title" => "Test"
            ]
        ]);
        $kernel->boot();
        $container = $kernel->getContainer();

        $service = $container->get("api_route.openapi_generator");
        self::assertInstanceOf(OpenApiGenerator::class, $service);
    }
}

class TestKernel extends Kernel {

    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        parent::__construct('test', true);
    }

    public function registerBundles()
    {
        return [new ApiRouteBundle()];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->prependExtensionConfig("api_route", $this->config);
        });
    }

    public function getCacheDir()
    {
        return parent::getCacheDir().spl_object_hash($this);
    }
}