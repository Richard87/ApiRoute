<?php


namespace Richard87\ApiRoute\Service;

use Richard87\ApiRoute\Attributes\ApiRoute;
use Richard87\ApiRoute\Attributes\CollectionRoute;
use Richard87\ApiRoute\Attributes\Description;
use Richard87\ApiRoute\Attributes\Property;

class ClassDescriptor
{
    /** @var ActionDescriptor[] */
    public array $actions = [];
    /** @var PropertyDescriptor[] */
    public array $properties = [];

    public Description $description;
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
        $descriptionList = $this->reflection->getAttributes(Description::class);
        $descriptionAttr = $descriptionList[0] ?? null;
        $this->description = $descriptionAttr ? $descriptionAttr->newInstance() : new Description();
        $this->name = FormatName::format($this->reflection->getShortName(), true);

        $classAttributes = $this->reflection->getAttributes();
        foreach ($classAttributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof ApiRoute) {
                $this->actions[] = ActionDescriptor::FromClass($instance, $this);
            }
        }

        foreach ($this->reflection->getProperties() as $reflProp){
            foreach ($reflProp->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();
                if ($instance instanceof Property) {
                    $this->properties[] = PropertyDescriptor::FromProperty($instance, $reflProp);
                }

                if ($instance instanceof ApiRoute) {

                    if ($instance  instanceof CollectionRoute) {
                        $instance->isItemOperation = true;
                    }

                    $this->actions[] = ActionDescriptor::FromProperty($instance, $this,$reflProp);
                }
            }
        }

        foreach ($this->reflection->getMethods() as $reflectionMethod){
            foreach ($reflectionMethod->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();
                if ($instance instanceof Property) {
                    $this->properties[] = PropertyDescriptor::FromMethod($instance, $reflectionMethod);
                }

                if ($instance instanceof ApiRoute) {
                    /** @var ApiRoute $instance */
                    $instance = $attribute->newInstance();

                    if ($instance instanceof CollectionRoute) {
                        $instance->isItemOperation = true;
                    }

                    $this->actions[] = ActionDescriptor::FromMethod($instance, $this,$reflectionMethod);
                }
            }
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRef(): string
    {
        return self::ConvertClassToRef($this->getClass());
    }

    public function getClass(): string {
        return $this->class;
    }

    public function hasActions(): bool
    {
        return count($this->actions) > 0;
    }

    public static function ConvertClassToRef(string $class): string {
        $ref = str_replace("/", "_", $class);
        return strtolower("api_". $ref);
    }
}