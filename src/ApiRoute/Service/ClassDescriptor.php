<?php


namespace Richard87\ApiRoute\Service;

use Richard87\ApiRoute\Attributes\ApiRoute;
use Richard87\ApiRoute\Attributes\CollectionRoute;
use Richard87\ApiRoute\Attributes\ApiResource;
use Richard87\ApiRoute\Attributes\Property;

class ClassDescriptor
{
    /** @var ApiRoute[] */
    public array $actions = [];
    /** @var PropertyDescriptor[] */
    public array $properties = [];

    public ApiResource $description;
    public string $name;
    private string $class;
    private \ReflectionClass $reflection;


    public function getBasePath(): string
    {
        $basePath = $this->description->path ?? "/api/{$this->name}";
        $basePath = str_replace([" ", "//", "\\"], "-", $basePath);
        $basePath = strtolower($basePath);

        return $basePath;
    }

    public function __construct(string $class) {
        $this->class = $class;
        $this->reflection = new \ReflectionClass($class);
        $descriptionList = $this->reflection->getAttributes(ApiResource::class);
        $descriptionAttr = $descriptionList[0] ?? null;
        $this->description = $descriptionAttr ? $descriptionAttr->newInstance() : new ApiResource();
        $this->name = FormatName::format($this->reflection->getShortName(), true);

        $classAttributes = $this->reflection->getAttributes(ApiRoute::class,\ReflectionAttribute::IS_INSTANCEOF);
        foreach ($classAttributes as $attribute) {
            /** @var ApiRoute $instance */
            $instance = $attribute->newInstance();
            $instance->withClass($this->reflection, $this);
            $this->actions[] = $instance;
        }

        foreach ($this->reflection->getProperties() as $reflProp){
            foreach ($reflProp->getAttributes(ApiRoute::class,\ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                /** @var ApiRoute $instance */
                $instance = $attribute->newInstance();

                if ($instance instanceof CollectionRoute) {
                    $instance->isItemOperation = true;
                }
                $instance->withProperty($reflProp, $this);
                $this->actions[] = $instance;
            }
        }

        foreach ($this->reflection->getMethods() as $reflectionMethod){
            foreach ($reflectionMethod->getAttributes(ApiRoute::class,\ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                /** @var ApiRoute $instance */
                $instance = $attribute->newInstance();

                if ($instance instanceof CollectionRoute) {
                    $instance->isItemOperation = true;
                }

                $instance->withMethod($reflectionMethod,$this);
                $this->actions[] = $instance;

            }
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClass(): string {
        return $this->class;
    }

    public function hasActions(): bool
    {
        return count($this->actions) > 0;
    }
}