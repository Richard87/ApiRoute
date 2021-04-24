<?php


namespace Richard87\ApiRoute\Service;


use Symfony\Component\Routing\RouterInterface;

class OpenApiGenerator
{
    public const ROUTE_ATTR = "_api_route_route";
    public const SCHEMA_ATTR = "_api_route_schema";
    public const REFS_ATTR = "_api_route_refs";

    private RouterInterface $router;

    public function __construct(RouterInterface $router, array $info)
    {
        $this->router = $router;
    }

    public function getDefinition(): array
    {

        $routeCollection = $this->router->getRouteCollection();

        $classes = [];
        $paths = [];
        foreach ($routeCollection->all() as $route) {
            if (!$route->getDefault(self::ROUTE_ATTR)) {
                continue;
            }


            $classes[] = $route->getDefault(self::REFS_ATTR);
            $path = $route->getPath();
            $method = strtolower($route->getMethods()[0]);

            $paths[$path][$method] = $route->getDefault(self::SCHEMA_ATTR);
        }

        $classes = array_merge(...$classes);

        $components = [];
        foreach ($classes as $ref => $class) {
            $descriptor = new ClassDescriptor($class);
            $components[$descriptor->getRef()] = [
                'type' => "object",
                'properties' => [
                    "@id" => [
                        "readOnly" => true,
                        "type" => "string",
                    ]
                ],
            ];
            foreach ($descriptor->properties as $property) {
                $components[$descriptor->getRef()]['properties'][$property->getName()] = $property->getSchema();
            }
        }

        return [
            "openapi" => "3.0.0",
            "basePath" => "",
            "info" => [
                "title" => "ApiRoute API",
                "version" => "0.0.1",
            ],
            "version" => "3",
            "paths" => $paths,
            "components" => [
                "schemas" => $components
            ]
        ];
    }
}