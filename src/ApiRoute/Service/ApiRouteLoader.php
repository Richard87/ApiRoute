<?php declare(strict_types=1);

namespace Richard87\ApiRoute\Service;


use JetBrains\PhpStorm\ArrayShape;
use Richard87\ApiRoute\Attributes\ApiRoute;
use Richard87\ApiRoute\Controller\RestActions\CollectionAction;
use Richard87\ApiRoute\Controller\RestActions\CreateAction;
use Richard87\ApiRoute\Controller\RestActions\DeleteAction;
use Richard87\ApiRoute\Controller\RestActions\GetAction;
use Richard87\ApiRoute\Controller\RestActions\UpdateAction;
use Richard87\ApiRoute\Exceptions\ApiException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Config\Loader\Loader;

class ApiRouteLoader extends Loader
{
    public const REQUEST_BODY = "_api_route_request_body_class";
    public const RESPONSE_BODY = "_api_route_response_body_class";
    public const ROUTE_ATTR = "_api_route_route";
    public const TAG_NAME = "_api_route_tag_name";
    public const SUMMARY = "_api_route_summary";
    const SYMFONY_CONTROLLER = '_controller';
    private bool $isLoaded = false;

    public function __construct(
        private FindClassDescriptors $findClassDescriptors,
        private string $basePath,
        private string $docsEndpoint,
        private bool $enableSwagger,
        private string $projectDir,
    )
    {
    }

    public function load($path, string $type = null): RouteCollection
    {
        $locator = new FileLocator([$this->projectDir, $this->projectDir . "/config", $this->projectDir . "/config/routes"]);

        if (!is_dir($dir = $locator->locate($path))) {
            throw new ApiException("You must specify a folder (could not find $path)!");
        }

        $routeCollection = new RouteCollection();
        if (false === $this->isLoaded) {
            $routeCollection->add("_api_route_openapi_endpoint", new Route(
                $this->docsEndpoint,
                defaults: ['_controller' => 'api_route.openapi_controller'],
                methods: ["GET"]
            ));
            if ($this->enableSwagger) {
                $routeCollection->add("_api_route_swagger_endpoint", new Route(
                    $this->basePath . "/",
                    defaults: ['_controller' => 'api_route.swagger_controller'],
                    methods: ["GET"]
                ));
            }
        }


        $classes = $this->findClassDescriptors->findAttributes($dir);
        foreach ($classes as $class) {
            foreach ($class->actions as $action) {
                $routeCollection->add($action->getPathName(), $this->createRoute($action));
            }
        }

        return $routeCollection;
    }

    public function supports($resource, string $type = null): bool
    {
        return $type === "api_route";
    }

    private function createRoute(ApiRoute $apiRoute): Route
    {
        $routeRequirements = []; // List of required route parameters

        if ($apiRoute->isItemOperation) {
            $routeRequirements['id'] = "\d+"; //TODO: Introspect this information from Class Resource!
        }

        return new Route(
            $apiRoute->getPath(),
            defaults: [
            self::SYMFONY_CONTROLLER    => $apiRoute->getController(),
            self::ROUTE_ATTR    => true,
            self::REQUEST_BODY  => $apiRoute->getRequestBody(),
            self::RESPONSE_BODY => $apiRoute->getResponseBody(),
            self::TAG_NAME      => $apiRoute->descriptor->getName(),
            self::SUMMARY       => $apiRoute->descriptor->description->summary,
            ],
            requirements: $routeRequirements,
            methods: [$apiRoute->method],
        );
    }
}