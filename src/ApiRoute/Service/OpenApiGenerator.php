<?php


namespace Richard87\ApiRoute\Service;


use Richard87\ApiRoute\Attributes\ApiRoute;
use Richard87\ApiRoute\Controller\MessengerActionController;
use Richard87\ApiRoute\Controller\RestActions\CollectionAction;
use Richard87\ApiRoute\Controller\RestActions\CreateAction;
use Richard87\ApiRoute\Controller\RestActions\DeleteAction;
use Richard87\ApiRoute\Controller\RestActions\GetAction;
use Richard87\ApiRoute\Controller\RestActions\UpdateAction;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class OpenApiGenerator
{
    public function __construct(
        private RouterInterface $router,
        private PropertyMapperService $propertyMapper,
        private array $info,
        private string $basePath
    ){}

    public function getDefinition(): array
    {

        $routeCollection = $this->router->getRouteCollection();

        $paths = [];
        $outputs = [];
        $inputs = [];
        foreach ($routeCollection->all() as $routeName => $route) {
            if (!$route->getDefault(ApiRouteLoader::ROUTE_ATTR)) {
                continue;
            }

            if ($output = $route->getDefault(ApiRouteLoader::RESPONSE_BODY))
                $outputs[] = $output;
            if ($input = $route->getDefault(ApiRouteLoader::REQUEST_BODY))
                $inputs[] = $input;

            $path = $route->getPath();
            $method = strtolower($route->getMethods()[0]);
            $paths[$path][$method] = $this->createSchema($routeName, $route);
        }

        $components = $this->mapProperties($inputs, $outputs);

        return [
            "openapi" => "3.0.0",
            "basePath" => $this->basePath,
            "info" => $this->info,
            "version" => "3",
            "paths" => $paths,
            "components" => [
                "schemas" => $components,
            ],
        ];
    }

    private function createSchema(string $routeName, Route $route): array
    {
        $input = $route->getDefault(ApiRouteLoader::REQUEST_BODY);
        $output = $route->getDefault(ApiRouteLoader::RESPONSE_BODY);
        $controller = $route->getDefault(ApiRouteLoader::SYMFONY_CONTROLLER);

        $schema = [
            "operationId" => $routeName,
            "tags"        => [$route->getDefault(ApiRouteLoader::TAG_NAME)], //TODO: Possibly add input and output her as well...
            "responses"   => ["404" => ['description' => 'Resource not found']],
            "summary"     => $route->getDefault(ApiRouteLoader::SUMMARY),
            "parameters"  => [],
        ];
        foreach ($route->getRequirements() as $name => $regex) {
            $schema['parameters'][] = [
                'name'     => $name,
                'in'       => 'path',
                'required' => true,
                'schema'   => ['type' => 'string'],
            ];
        }

        if ($input) {
            $class = $input;
            $optional = str_starts_with($class,"?");
            if ($optional)
                $class = substr($class,1);

            $ref = self::ConvertClassToRef($class);
            $schema['requestBody']['content']['application/ld+json']['schema']['$ref'] = $ref;
        }

        $isMessage = $input && in_array(MessengerActionController::class, class_parents($controller));
        $isDelete  = $route->getMethods()[0] === "delete";

        $content = null;
        if ($output) {
            $content = ['application/ld+json' => ['schema' => ['$ref' => self::ConvertClassToRef($output)]]];
        }

        //TODO Move status code to ApiRoute
        if ($isMessage && !$output) {
            $schema["responses"]["202"] = ['description' => 'Resource queued'];
        } elseif ($isDelete && !$output) {
            $schema["responses"]["204"] = ['description' => 'Resource deleted'];
        } elseif ($isDelete && $output) {
            $schema["responses"]["202"] = ['description' => 'Resource deleted', "content" => $content];
        } elseif ($output) {
            $schema["responses"]["201"] = ['description' => 'Resource created', "content" => $content];
        } elseif ($input) {
            $schema["responses"]["201"] = ['description' => 'Success'];
        } else {
            $schema["responses"]["200"] = ['description' => 'Success'];
        }

        return $schema;
    }

    public static function ConvertClassToRef(string $class): string {
        $ref = str_replace("/", "_", $class);
        return strtolower("api_". $ref);
    }

    private function mapProperties(array $inputs, array $outputs): array
    {
        $components = [];
        $arr        = array_unique([...$inputs, ...$outputs]);
        foreach ($arr as $class) {
            $schema = [
                'type'       => "object",
                'properties' => [
                    "@id" => [
                        "readOnly" => true,
                        "type"     => "string",
                    ],
                ],
            ];

            //TODO: Load all properties if empty (DTO)
            $propertyDescriptors = $this->propertyMapper->findProperties($class);
            foreach ($propertyDescriptors as $property) {
                $schema['properties'][$property->getName()] = $property->getSchema();
            }

            $ref              = self::ConvertClassToRef($class);
            $components[$ref] = $schema;
        }
        return $components;
    }
}