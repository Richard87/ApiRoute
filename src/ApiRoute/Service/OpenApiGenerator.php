<?php


namespace Richard87\ApiRoute\Service;


use Doctrine\Common\Collections\Collection;
use Richard87\ApiRoute\Attributes\ApiRoute;
use Richard87\ApiRoute\Exceptions\ApiException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class OpenApiGenerator
{
    public function __construct(
        private RouterInterface $router,
        private PropertyMapperService $propertyMapper,
        private array $info,
    ){}

    public function getDefinition(): array
    {

        $routeCollection = $this->router->getRouteCollection();

        $paths = [];
        $outputs = [];
        $inputs = [];
        foreach ($routeCollection->all() as $routeName => $route) {
            $apiRoute = $route->getDefault(ApiRoute::ROUTE_ATTR);
            if (!$apiRoute instanceof ApiRoute)
                continue;


            if ($output = $apiRoute->getResponseBody())
                $outputs[] = $output;
            if ($input = $apiRoute->getRequestBody())
                $inputs[] = $input;

            $path = $route->getPath();
            $method = strtolower($route->getMethods()[0]);
            $paths[$path][$method] = $this->createOperationSchema($routeName, $route, $apiRoute);
        }

        $classes    = array_unique([...$inputs, ...$outputs]);
        $components = [];
        foreach ($classes as $class) {
            $ref = self::ConvertClassToRef($class);
            $components[$ref] = $this->mapProperties($class);
        }
        return [
            "openapi" => "3.0.0",
            "info" => $this->info,
            "paths" => $paths,
            "components" => ["schemas" => $components],
        ];
    }

    private function createOperationSchema(string $routeName, Route $route, ApiRoute $apiRoute): array
    {
        $input = $apiRoute->getRequestBody();
        $output = $apiRoute->getResponseBody();

        $schema = [
            "operationId" => $routeName,
            "tags"        => $apiRoute->getTags(),
            "responses"   => ["404" => ['description' => 'Resource not found']],
            "summary"     => $apiRoute->getSummary(),
            "parameters"  => [],
        ];
        foreach ($route->getRequirements() as $name => $regex) {
            $schema['parameters'][] = [
                'name'     => $name,
                'in'       => 'path',
                'required' => true,
                'schema'   => ['type' => 'string', 'pattern' => $regex],
            ];
        }

        foreach ($apiRoute->getQueryParameters() as $name => $type) {
            $optional = str_starts_with($type,"?");
            if ($optional)
                $type = substr($type,1);

            $schema['parameters'][] = [
                'name'     => $name,
                'in'       => 'query',
                'required' => !$optional,
                'schema'   => self::mapStringType($type),
            ];
        }

        if ($input) {
            $class = $input;
            $optional = str_starts_with($class,"?");
            if ($optional)
                $class = substr($class,1);

            $ref ="#/components/schemas/" . self::ConvertClassToRef($class);
            $schema['requestBody']['content'][$apiRoute->getContentType()]['schema']['$ref'] = $ref;
        }

        $schema["responses"]["$apiRoute->statusCode"]['description'] = match ($apiRoute->statusCode) {
            201 => 'Resource created',
            202 => "Resource queued",
            204 => "Resource deleted",
            default => 'Success',
        };

        if ($output) {
            $ref =  self::ConvertClassToRef($output);
            $schema["responses"]["$apiRoute->statusCode"]['content'] = [
                'application/ld+json' => ['schema' => ['$ref' => "#/components/schemas/$ref"]]
            ];
        }
        return $schema;
    }

    private function mapProperties(string $class): array
    {
        $schema = [
            'type'       => "object",
            'properties' => [
                "@id" => [
                    "readOnly" => true,
                    "type"     => "string",
                ],
            ],
        ];

        $propertyDescriptors = $this->propertyMapper->findProperties($class);
        foreach ($propertyDescriptors as $property) {
            $schema['properties'][$property->getName()] = $property->getSchema();
        }

        return $schema;
    }

    public static function ConvertClassToRef(string $class): string {
        $ref = str_replace(["/", "\\"], "_", $class);
        $ref = strtolower($ref);
        return $ref;
    }

    public static function mapStringType(string $type, bool $nullable = false) : array {
        if (str_starts_with($type,"?")) {
            $nullable = true;
            $type = substr($type, 1);
        }

        return match ($type) {
            \DateTime::class,"DateTime",\DateTimeImmutable::class, \DateTimeInterface::class => ["type" => "string", "example" => "1985-04-12T23:20:50.52Z", "nullable" => $nullable],
            "string" => ["type" => "string", "nullable" => $nullable],
            "bool" => ['type' => "string", "example" => "true", "nullable" => $nullable],
            "float" => ['type' => "string", "example" => "1.0", "nullable" => $nullable],
            "int" => ['type' => "string", "example" => "1", "nullable" => $nullable],
            default => throw new ApiException("Illigal type '$type', must be serializable to string")
        };
    }

    public static function mapComplexType(string $type, bool $nullable = false): array {
        if (str_starts_with($type,"?")) {
            $nullable = true;
            $type = substr($type, 1);
        }
        if (str_contains($type,"|")) {
            $types = explode("|", $type);
            if (in_array("null",$types))
                $nullable = true;
            return self::mapComplexType($types[0] ?? "mixed", $nullable);
        }

        $schema = match ($type) {
            \DateTime::class, "DateTime", \DateTimeImmutable::class, \DateTimeInterface::class => ["type" => "string", "example" => "1985-04-12T23:20:50.52Z"],
            "string" => ["type" => "string"],
            "bool" => ['type' => "string", "example" => true],
            "float" => ['type' => "string", "example" => "1.0"],
            "int" => ['type' => "string", "example" => 1],
            "null" => ['type' => 'null'],
            default => null
        };

        if (!$schema && ($type === "array" || in_array(Collection::class, class_implements($type)))) {
            //TODO: Try to find out specific type
            $schema = ["type" => "array", 'items' => ['type' => 'string']];
        }

        if ($schema) {
            if ($nullable)
                $schema['nullable'] = true;

            return $schema;
        }


        //TODO: Make sure this $ref is mapped
        $ref = self::ConvertClassToRef($type);
        $schema = ['$ref' => "#/components/schemas/$ref"];
        if ($nullable)
            $schema['nullable'] = true;

        return $schema;
    }
}