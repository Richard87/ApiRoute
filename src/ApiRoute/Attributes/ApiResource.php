<?php


namespace Richard87\ApiRoute\Attributes;


use Attribute;
use Richard87\ApiRoute\Service\FormatName;

#[Attribute(Attribute::TARGET_CLASS)]
/**
 * Tells ApiRoute that the object have it's own identifier.
 */
class ApiResource
{
    public function __construct(
        public ?string $path = null,
        public ?string $name = null,
        public ?string $summary = null,
        public string $identifier = "id",
    ){}

    public static function findOnClass(string|\ReflectionClass $class):?self
    {
        if (is_string($class)) {
            $class = new \ReflectionClass($class);
        }

        $apiResources = $class->getAttributes(ApiResource::class,\ReflectionAttribute::IS_INSTANCEOF);
        if (count($apiResources) === 0) {
            return null;
        }

        /** @var ApiResource $apiResource */
        $apiResource = $apiResources[0]->newInstance();

        if (!$apiResource->name)
            $apiResource->name = FormatName::format($class->getShortName(), true);

        return $apiResource;
    }

    public function getName(): string
    {
        return $this->name;
    }
}