<?php declare(strict_types=1);

namespace Richard87\ApiRoute\Service;


use Richard87\ApiRoute\Exceptions\ApiException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Config\Loader\Loader;

class ApiRouteLoader extends Loader
{
    private bool $isLoaded = false;

    public function __construct(
        private FindClassDescriptors $findClassDescriptors,
        private string $basePath,
        private bool $enableSwagger,
        private string $projectDir,
    )
    {
        if (str_ends_with($this->basePath,"/")) {
            $this->basePath = substr($this->basePath, 0, -1);
        }
    }


    public function load($path, string $type = null): RouteCollection
    {
        $locator = new FileLocator([$this->projectDir, $this->projectDir ."/config", $this->projectDir ."/config/routes"]);

        if (!is_dir($dir = $locator->locate($path))) {
            throw new ApiException("You must specify a folder (could not find $path)!");
        }

        $routeCollection = new RouteCollection();
        if (false === $this->isLoaded) {
            $routeCollection->add("_api_route_openapi_endpoint",new Route(
                $this->basePath . "/docs.json",
                defaults: ['_controller' => 'api_route.openapi_controller'],
                methods: ["GET"]
            ));
            if ($this->enableSwagger) {
                $routeCollection->add("_api_route_swagger_endpoint",new Route(
                    $this->basePath . "/",
                    defaults: ['_controller' => 'api_route.swagger_controller'],
                    methods: ["GET"]
                ));
            }
        }


        $classes = $this->findClassDescriptors->findAttributes($dir);
        foreach ($classes as $class) {
            foreach ($class->actions as $action) {
                $routeCollection->add($action->getPathName(), $action->getRoute());
            }
        }

        return $routeCollection;
    }

    public function supports($resource, string $type = null): bool
    {
        return $type === "api_route";
    }
}