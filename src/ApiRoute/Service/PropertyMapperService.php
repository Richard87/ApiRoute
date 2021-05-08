<?php


namespace Richard87\ApiRoute\Service;


use ReflectionAttribute;
use Richard87\ApiRoute\Attributes\ApiRoute;
use Richard87\ApiRoute\Attributes\CollectionRoute;
use Richard87\ApiRoute\Attributes\ApiResource;
use Richard87\ApiRoute\Attributes\Property;

class PropertyMapperService
{
    /**
     * @param string $class
     * @return PropertyDescriptor[]
     * @throws \ReflectionException
     */
    public function findProperties(string $class): array {
        $reflection = new \ReflectionClass($class);

        $properties = [];

        foreach ($reflection->getProperties() as $reflProp){
            foreach ($reflProp->getAttributes(Property::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                /** @var Property $instance */
                $instance = $attribute->newInstance();
                $properties[] = PropertyDescriptor::FromProperty($instance, $reflProp);
            }
        }

        foreach ($reflection->getMethods() as $reflectionMethod){
            foreach ($reflectionMethod->getAttributes(Property::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                /** @var Property $instance */
                $instance = $attribute->newInstance();
                $properties[] = PropertyDescriptor::FromMethod($instance, $reflectionMethod);
            }
        }

        return $properties;
    }

}