<?php


namespace Richard87\ApiRoute\Attributes;


use Attribute;
use Richard87\ApiRoute\Controller\ActionController;
use Richard87\ApiRoute\Controller\MessengerActionController;
use Richard87\ApiRoute\Exceptions\ApiException;
use Richard87\ApiRoute\Service\ClassDescriptor;
use Richard87\ApiRoute\Service\FormatName;
use Symfony\Component\Routing\Route;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ApiRoute
{
    public const ROUTE_ATTR = "_api_route_route";

    private \ReflectionClass $reflectionClass;
    private ?\ReflectionMethod $reflectionMethod = null;
    private ?\ReflectionProperty $reflectionProperty = null;

    public ?string $target = null;
    private ?ApiResource $apiResource = null;

    public function __construct(
        public ?string $method = "post",
        public ?string $input = null,
        public ?string $output = null,

        public ?string $controller = null,
        public ?string $path = null,
        public int $statusCode = 200,
        public ?string $security = null,
        public ?string $pathName = null,
        public ?string $summary = null,
        public ?array $tags = null,
        public bool $isItemOperation = true,
        public bool $flush = true,
    ){
        $this->method = strtolower($this->method);
    }

    public function withClass(\ReflectionClass $class, ?ApiResource $descriptor): void {
        $this->reflectionClass = $class;
        $this->apiResource      = $descriptor;
        $this->target = $class->getName();

        if ($this->path === null) {
            $targetClass = $this->controller ?? $this->input;
            $reflection = new \ReflectionClass($targetClass);
            $path = $reflection->getShortName();

            $this->path = FormatName::format($path, !$this->isItemOperation);
        }
    }

    public function withMethod(\ReflectionMethod $method, ?ApiResource $descriptor): void {
        $this->reflectionClass = $method->getDeclaringClass();
        $this->reflectionMethod = $method;
        $this->apiResource      = $descriptor;
        $this->target = $this->reflectionClass->getName() . "::" . $this->reflectionMethod->getName() . "()";

        if ($this->path === null) {
            $shouldBePlural = $method->isStatic() | !$this->isItemOperation;
            $this->path = FormatName::format($method->getShortName(), $shouldBePlural);
        }
    }

    public function withProperty(\ReflectionProperty $property, ?ApiResource $descriptor): void {
        $this->reflectionClass = $property->getDeclaringClass();
        $this->reflectionProperty = $property;
        $this->apiResource      = $descriptor;
        $this->target = $this->reflectionClass->getName() . "::" . $this->reflectionProperty->getName();

        if ($this->path === null) {
            $this->path = FormatName::format($property->getName(), !$this->isItemOperation);
        }
    }

    /**
     * Return a dictionary of query parameters. Prefix optional parameter type with ?.
     * For example ["from" => \Datetime, "email": "?string", "to", "?\DateTime"]
     *
     */
    public function getQueryParameters(): array {
        //TODO: Add optional/required query parameters depending on method
        return [];
    }

    /**
     * Return request body if necessary.
     *
     * For example: "\App\Message\RequestDTO"
     */
    public function getRequestBody(): ?string {
        return $this->input;
    }

    /**
     * Return response body if necessary.
     *
     * For example: "\App\Message\ResponseDTO"
     */
    public function getResponseBody(): ?string {
        return $this->output;
    }

    public function getTags(): array {
        $tags = [$this->getResourceName()];

        if ($this->input && $apiResource = ApiResource::findOnClass($this->input)) {
            $tags[] = $apiResource->getName();
        }

        if ($this->output && $apiResource = ApiResource::findOnClass($this->output)) {
            $tags[] = $apiResource->getName();
        }
        return $tags;
    }

    public function getContentType(): string {
        return "application/ld+json";
    }

    public function getSummary(): ?string {
        return $this->apiResource?->summary ?? null;
    }

    protected function getPath(string $basePath):string {
        $url = $basePath;
        $path = $this->path;
        $resourceName = FormatName::format($this->getResourceName(),true);

        if ($path && str_starts_with($path,"/")) {
            return $path;
        }

        if (str_ends_with($url, "/")) {
            $url = substr($url, 0, -1);
        }

        $url .= "/" . $resourceName;

        if ($this->isItemOperation) {
            $url .= "/{id}";
        }

        if ($path) {
            $url .= "/" . $path;
        }

        return strtolower($url);
    }

    public function getResourceName(): string {
        return $this->apiResource?->getName() ?? $this->reflectionClass->getShortName();
    }

    public function getName(): string
    {
        if ($this->reflectionProperty) {
            return $this->reflectionProperty->getName();
        }

        if ($this->reflectionMethod) {
            return $this->reflectionMethod->getName();
        }

        if ($this->controller) {
            return (new \ReflectionClass($this->controller))->getShortName();
        }

        if ($this->input) {
            return (new \ReflectionClass($this->input))->getShortName();
        }

        $className = $this->reflectionClass->getName();
        $method = $this->reflectionMethod?->getName() ?? $this->reflectionProperty?->getName() ?? "";
        $attributeName = (new \ReflectionClass($this::class))->getShortName();
        throw new ApiException("Could not find api name! 'controller' or 'input' must be specified on class attributes ('$attributeName' on $className::$method())");
    }

    public function getPathName(): string
    {
        if ($this->pathName) {
            return $this->pathName;
        }

        $name = FormatName::format($this->getName());
        $resourceName = $this->getResourceName();
        $formatted = FormatName::format("ApiRoute $this->method $resourceName $name");
        $formatted = str_replace("-", "_", $formatted);
        return "_". $formatted;
    }

    public function getController(): string
    {
        if ($this->controller) {
            return $this->controller;
        }

        if ($this->reflectionProperty) {
            return ActionController::class;
        }

        if ($this->reflectionMethod) {
            return ActionController::class;
        }

        // This is a Message handler
        if ($this->input) {
            return MessengerActionController::class;
        }

        $className = $this->reflectionClass->getName();
        $methodName = $this->reflectionMethod?->getName() ?? $this->reflectionProperty->getName() ?? "";
        $attributeName = (new \ReflectionClass($this::class))->getShortName();
        throw new ApiException("Could not find api controller! 'controller' or 'input' must be specified on class attributes ('$attributeName' on $className::$methodName())");
    }

    public function createRoute(string $basePath): Route
    {
        $routeRequirements = []; // List of required route parameters

        if ($this->isItemOperation && $this->apiResource) {
            $identifier = $this->apiResource->identifier;
            $reflection = $this->reflectionClass->getProperty($identifier);
            $idType     = (string) $reflection->getType();

            $routeRequirements[$identifier] = match($idType) {
               "int" => "\d+",
               default => ".+",
            };
        }

        return new Route(
            $this->getPath($basePath),
            defaults: [
                "_controller" => $this->getController(),
                self::ROUTE_ATTR    => $this,
            ],
            requirements: $routeRequirements,
            methods: [$this->method],
        );
    }

    public function __serialize(): array
    {
        return [
            'method' => $this->method,
            'input' => $this->input,
            'output' => $this->output,
            'controller' => $this->controller,
            'path' => $this->path,
            'security' => $this->security,
            'pathName' => $this->pathName,
            'summary' => $this->summary,
            'tags' => $this->tags,
            'isItemOperation' => $this->isItemOperation,
            'target' => $this->target,
        ];
    }

    public function __unserialize(array $data): void
    {
            $this->method = $data['method'];
            $this->input = $data['input'];
            $this->output = $data['output'];
            $this->controller = $data['controller'];
            $this->path = $data['path'];
            $this->security = $data['security'];
            $this->pathName = $data['pathName'];
            $this->summary = $data['summary'];
            $this->tags = $data['tags'];
            $this->isItemOperation = $data['isItemOperation'];
            $this->target = $data['target'];

            [$class, $methodOrProperty] = explode("::", $data['target']);
            $this->reflectionClass = new \ReflectionClass($class);
            if ($methodOrProperty) {
                if (str_ends_with("()", $methodOrProperty)) {
                    $this->reflectionMethod = $this->reflectionClass->getMethod(substr($methodOrProperty, 0, -2));
                } else {
                    $this->reflectionProperty = $this->reflectionClass->getProperty($methodOrProperty);
                }
            }
    }
}