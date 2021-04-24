<?php


namespace Richard87\ApiRoute\Service;

use Richard87\ApiRoute\Attributes\ApiRoute;
use Richard87\ApiRoute\Controller\ActionController;
use Richard87\ApiRoute\Controller\RestActions\CollectionAction;
use Richard87\ApiRoute\Controller\RestActions\CreateAction;
use Richard87\ApiRoute\Controller\RestActions\DeleteAction;
use Richard87\ApiRoute\Controller\RestActions\GetAction;
use Richard87\ApiRoute\Controller\RestActions\UpdateAction;
use Richard87\ApiRoute\Exceptions\ApiException;
use Symfony\Component\Routing\Route;

class ActionDescriptor
{
    private ?\ReflectionMethod $methodAction = null;
    private ?\ReflectionProperty $propertyAction = null;

    public function __construct(
        public ApiRoute $action,
        public ClassDescriptor $description,
    ){}


    public static function FromProperty(ApiRoute $action, ClassDescriptor $description, \ReflectionProperty $reflectionProperty): ActionDescriptor
    {
        if ($action->path === null) {
            $action->path = FormatName::format($reflectionProperty->getName(), !$action->isItemOperation);
        }

        $actionDescriptor = new self($action, $description);
        $actionDescriptor->propertyAction = $reflectionProperty;
        return $actionDescriptor;
    }

    public static function FromMethod(ApiRoute $action, ClassDescriptor $description, \ReflectionMethod $reflectionMethod): ActionDescriptor
    {
        if ($action->path === null) {
            $shouldBePlural = $reflectionMethod->isStatic() | !$action->isItemOperation;
            $action->path = FormatName::format($reflectionMethod->getShortName(), $shouldBePlural);
        }

        $actionDescriptor = new self($action, $description);
        $actionDescriptor->methodAction = $reflectionMethod;
        return $actionDescriptor;
    }

    public static function FromClass(ApiRoute $action, ClassDescriptor $descriptor): self
    {
        if ($action->path === null) {
            $targetClass = $action->controller ?? $action->input;
            $reflection = new \ReflectionClass($targetClass);
            $path = $reflection->getShortName();

            $action->path = FormatName::format($path, !$action->isItemOperation);
        }

        return new self($action, $descriptor);
    }

    public function getMethod(): string {
        return strtolower($this->action->method);
    }

    public function getPath():string {
        $url = $this->description->getBasePath();
        $path = $this->action->path;

        if ($path && str_starts_with($path,"/")) {
            return $path;
        }

        if (str_ends_with($url, "/")) {
            $url = substr($url, 0, -1);
        }

        if ($this->action->isItemOperation) {
            $url .= "/{id}";
        }

        if ($path === "") {
            return strtolower($url);
        }

        if ($path === null) {
            return strtolower("$url/$path");
        }

        return strtolower("$url/$path");
    }

    public function getName(): string
    {
        if ($this->propertyAction) {
            return $this->propertyAction->getName();
        }

        if ($this->methodAction) {
            return $this->methodAction->getName();
        }

        if ($this->action->controller) {
            return (new \ReflectionClass($this->action->controller))->getShortName();
        }

        if ($this->action->input) {
            return (new \ReflectionClass($this->action->input))->getShortName();
        }

        $className = $this->description->getName();
        $attributeName = (new \ReflectionClass($this->action::class))->getShortName();
        throw new ApiException("Could not find api name! 'controller' or 'input' must be specified on class attributes ($className::$attributeName())");
    }

    public function getPathName(): string
    {
        if ($this->action->pathName) {
            return $this->action->pathName;
        }

        $name = FormatName::format($this->getName());
        $method = $this->getMethod();
        $resourceName = $this->description->getName();
        $formatted = FormatName::format("Api $method $resourceName $name");
        $formatted = str_replace("-", "_", $formatted);
        return $formatted;
    }

    public function getController(): string
    {
        if ($this->propertyAction) {
            return ActionController::class;
        }

        if ($this->methodAction) {
            return ActionController::class;
        }

        if ($this->action->controller) {
            return $this->action->controller;
        }

        if ($this->action->input) {
            return ActionController::class;
        }

        $className = $this->description->getName();
        $attributeName = (new \ReflectionClass($this->action::class))->getShortName();
        throw new ApiException("Could not find api controller! 'controller' or 'input' must be specified on class attributes ($className::$attributeName())");
    }

    public function getRoute(): Route
    {
        $routeRequirements = [];
        $schemaParameters = [];



        $isMessage = $this->action->input && !$this->action->controller && !$this->methodAction && !$this->propertyAction;
        $isDelete = $this->getMethod() === "delete";
        $isGet = $this->getMethod() === "get";

        $hasBody = !$isDelete && !$isGet;

        // Get content ? Only query variables + output
        // Message ? No output
        // Controller ?
        //    Get ? Query parameters + output
        //    Else ? Query parameters + Body + output
        // Method action?
        //    Get? Query parameters + output
        //    Else? Query parameters + body + output
        // Property action?
        //    Get? output only
        //    Else? body only

        $refs = [$this->description->getRef() => $this->description->getClass()];

        $schema = [
            "operationId" => $this->getPathName(),
            "tags" => [$this->description->getName()],
            "responses" => ["404" => ['description' => 'Resource not found']],
            "summary" => $this->description->description->summary,
            "parameters" => []
        ];

        if ($this->action->isItemOperation) {
            $schema['parameters'][] = [
                'name' => 'id',
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'string'],
            ];
            $routeRequirements['id'] = "\d+"; //TODO: Introspect this information from Class Resource!
        }

        $outputSchema = ['$ref' => null];
        if ($controller = $this->action->controller) {
            // TODO: Move this logic to Action, so Rest Actions can override it
            // If REST Action, return object
            if (in_array($controller, [CollectionAction::class, CreateAction::class, DeleteAction::class, GetAction::class, UpdateAction::class])) {

                if ($isDelete) {
                    $schema["responses"]["204"] = ['description' => 'Resource deleted'];
                } elseif ($isGet) {
                    $schema["responses"]["200"] = ['description' => 'Resource', "content" => ['application/json' => ['schema' => $outputSchema]]];
                } elseif ($isMessage) {
                    $schema["responses"]["200"] = ['description' => 'Resource queued'];
                } else {
                    $schema["responses"]["201"] = ['description' => 'Resource created', "content" => ['application/json' => ['schema' => $outputSchema]]];
                }
            }

            if (!method_exists($controller, "__invoke")) {
                throw new ApiException("Controller is not invokable! '$controller::__invoke()' missing");
            }
            $refl = new \ReflectionMethod($controller, "__invoke");
            $returnRefl = $refl->getReturnType();
            $class = (string)$returnRefl;
            $void = !$returnRefl;
            $nullable = $void || $returnRefl->allowsNull();


            $schema = $this->generateSchema($schema, $class, $void, $nullable, $refs);
            // return controller output
        } elseif ($this->methodAction) {
            // Return method output

            $returnRefl = $this->methodAction->getReturnType();
            $class = (string)$returnRefl;
            $void = !$returnRefl;
            $nullable = $void || $returnRefl->allowsNull();

            $schema = $this->generateSchema($schema, $class, $void, $nullable, $refs);

        } elseif ($this->propertyAction) {
            if ($isGet) {
                $returnRefl = $this->propertyAction->getType();
                $class = (string)$returnRefl;
                $void = !$returnRefl;
                $nullable = $void || $returnRefl->allowsNull();
                $schema = $this->generateSchema($schema, $class, $void, $nullable, $refs);
            } else {
                // else return class
                $class = $this->description->getClass();
                $schema = $this->generateSchema($schema, $class, false, false, $refs);
            }
        } else {
            $schema["responses"]["202"] = ['description' => 'Resource accepted'];
        }

        return new Route(
            $this->getPath(),
            defaults: [
                OpenApiGenerator::ROUTE_ATTR => true,
                '_controller' => $this->getController(),
                OpenApiGenerator::REFS_ATTR => $refs,
                OpenApiGenerator::SCHEMA_ATTR => $schema,
            ],
            requirements: $routeRequirements,
            methods: [$this->getMethod()],
        );
    }

    protected function createSchema(string $type, string $example, bool $nullable = false): array
    {
        $schema = [
            "200" => [
                'description' => 'Results',
                "content" => [
                    'application/json' => [
                        'schema' => [
                            "type" => $type,
                            "example" => $example
                        ]
                    ]
                ]
            ]
        ];

        if ($nullable) {
            $schema["202"] = [
                'description' => 'Accepted'
            ];
        }

        return $schema;
    }

    private function generateSchema(array $schema, string $class, bool $void, bool $nullable, array & $refs): array
    {
        if (str_starts_with($class, "?")) {
            $nullable = true;
            $class = substr($class,1);
        }

        if ($class === "null" || $class === "void" || $class === "false") {
            $void = true;
        }

        if ($void) {
            $schema["responses"]["200"] = ['description' => 'Ok'];
        } elseif ($class === \DateTime::class) {
            $schema["responses"]["200"] = $this->createSchema("date-time", "1985-04-12T23:20:50.52Z", $nullable);
        } elseif ($class === "string") {
            $schema["responses"]["200"] = $this->createSchema("string", "Some text", $nullable);
        } elseif ($class === "bool") {
            $schema["responses"]["200"] = $this->createSchema("boolean", "true", $nullable);
        } elseif ($class === "float") {
            $schema["responses"]["200"] = $this->createSchema("number", "5.0", $nullable);
        } elseif ($class === "int") {
            $schema["responses"]["200"] = $this->createSchema("number", "5", $nullable);
        } elseif ($class === "object") {
            $schema["responses"]["200"] = $this->createSchema("string", "", $nullable);
        } elseif ($class === "mixed") {
            $schema["responses"]["200"] = $this->createSchema("string", "", $nullable);
        } elseif ($class === "void") {
            $schema["responses"]["201"] = $this->createSchema("array", "", $nullable);
        } elseif ($class === "array") {
            $schema["responses"]["200"] = $this->createSchema("array", "", $nullable);
        } else {
            $ref = ClassDescriptor::ConvertClassToRef($class);
            $schema["responses"]["200"] = ['description' => 'Resource', "content" => ['application/json' => ['schema' => ['$ref' => $ref]]]];
            $refs[$ref] = $class;
        }
        return $schema;
    }

}