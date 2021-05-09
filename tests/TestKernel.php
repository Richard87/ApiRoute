<?php declare(strict_types=1);

namespace Richard87\ApiRoute\Tests;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Richard87\ApiRouteBundle\ApiRouteBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel {

    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        parent::__construct('test', true);
    }

    public function registerBundles():array
    {
        return [new ApiRouteBundle(), new FrameworkBundle()];
    }

    public function getProjectDir()
    {
        return __DIR__ . "/resources";
    }


    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) use($loader) {
            $container->prependExtensionConfig("api_route", $this->config);
            $container->prependExtensionConfig("framework", [
                "router" => [
                    "resource" => __DIR__ . "/resources/Config/routing.php",
                    "strict_requirements" => null,
                    "utf8" => true,
                ]
            ]);
        });
    }

    public function getCacheDir(): string
    {
        return parent::getCacheDir().spl_object_hash($this);
    }

    public function __destruct()
    {
        $this->rmCacheDir();
    }

    private function rmCacheDir(): void {
        $dir = $this->getCacheDir();
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}