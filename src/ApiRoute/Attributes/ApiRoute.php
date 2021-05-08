<?php


namespace Richard87\ApiRoute\Attributes;


use Attribute;
use Richard87\ApiRoute\Controller\ActionController;
use Richard87\ApiRoute\Controller\MessengerActionController;
use Richard87\ApiRoute\Exceptions\ApiException;
use Richard87\ApiRoute\Service\ClassDescriptor;
use Richard87\ApiRoute\Service\FormatName;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ApiRoute
{
    public \ReflectionClass $reflectionClass;
    public ClassDescriptor $descriptor;
    public ?\ReflectionMethod $reflectionMethod = null;
    public ?\ReflectionProperty $reflectionProperty = null;

    public function __construct(
        public ?string $method = "post",
        public ?string $input = null,
        public ?string $output = null,

        public ?string $controller = null,
        public ?string $path = null,
        public ?string $security = null,
        public ?string $pathName = null,
        public ?string $summary = null,
        public ?array $tags = null,
        public bool $isItemOperation = true,
    ){
        $this->method = strtolower($this->method);
    }

    public function withClass(\ReflectionClass $class, ClassDescriptor $descriptor): void {
        $this->reflectionClass = $class;
        $this->descriptor      = $descriptor;

        if ($this->path === null) {
            $targetClass = $this->controller ?? $this->input;
            $reflection = new \ReflectionClass($targetClass);
            $path = $reflection->getShortName();

            $this->path = FormatName::format($path, !$this->isItemOperation);
        }
    }

    public function withMethod(\ReflectionMethod $method, ClassDescriptor $descriptor): void {
        $this->reflectionClass = $method->getDeclaringClass();
        $this->reflectionMethod = $method;
        $this->descriptor      = $descriptor;

        if ($this->path === null) {
            $shouldBePlural = $method->isStatic() | !$this->isItemOperation;
            $this->path = FormatName::format($method->getShortName(), $shouldBePlural);
        }
    }

    public function withProperty(\ReflectionProperty $property, ClassDescriptor $descriptor): void {
        $this->reflectionClass = $property->getDeclaringClass();
        $this->reflectionProperty = $property;
        $this->descriptor      = $descriptor;

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
        return [];
    }

    /**
     * Return request body if necessary. Prefix type with ? to mark it as optional.
     *
     * For example: "?\App\Message\RequestDTO"
     */
    public function getRequestBody(): ?string {
        return null;
    }

    /**
     * Return response body if necessary.
     *
     * For example: "\App\Message\ResponseDTO"
     */
    public function getResponseBody(): ?string {
        return null;
    }

    public function getPath():string {
        $url = $this->descriptor->getBasePath();
        $path = $this->path;

        if ($path && str_starts_with($path,"/")) {
            return $path;
        }

        if (str_ends_with($url, "/")) {
            $url = substr($url, 0, -1);
        }

        if ($this->isItemOperation) {
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

        $className = $this->descriptor->getName();
        $attributeName = (new \ReflectionClass($this::class))->getShortName();
        throw new ApiException("Could not find api name! 'controller' or 'input' must be specified on class attributes ($className::$attributeName())");
    }

    public function getPathName(): string
    {
        if ($this->pathName) {
            return $this->pathName;
        }

        $name = FormatName::format($this->getName());
        $resourceName = $this->descriptor->getName();
        $formatted = FormatName::format("Api $this->method $resourceName $name");
        $formatted = str_replace("-", "_", $formatted);
        return $formatted;
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

        $className = $this->descriptor->getName();
        $attributeName = (new \ReflectionClass(static::class))->getShortName();
        throw new ApiException("Could not find api controller! 'controller' or 'input' must be specified on class attributes ($className::$attributeName())");
    }
}