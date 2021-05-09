<?php


namespace Richard87\ApiRoute\Service;


use ReflectionAttribute;
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

        // Return all properties if class has 0 properties
        if (count($properties) === 0)
            return $this->mapAllProperties($reflection);

        return $properties;
    }

    private function mapAllProperties(\ReflectionClass $reflection): array
    {
        $properties = [];
        foreach ($reflection->getProperties() as $reflProp){
            $properties[] = PropertyDescriptor::fromProperty(new Property(), $reflProp);
        }

        return $properties;
    }

}