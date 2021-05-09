<?php


namespace Richard87\ApiRoute\Service;

use Richard87\ApiRoute\Attributes\ApiRoute;
use Richard87\ApiRoute\Attributes\CollectionRoute;
use Richard87\ApiRoute\Attributes\ApiResource;

class ClassDescriptor
{
    public function mapClass(string $class): array {
        $reflectionClass = new \ReflectionClass($class);
        $apiResource = ApiResource::findOnClass($class);

        /** @var ApiRoute[] $actions */
        $actions = [];


        $classAttributes = $reflectionClass->getAttributes(ApiRoute::class,\ReflectionAttribute::IS_INSTANCEOF);
        foreach ($classAttributes as $attribute) {
            /** @var ApiRoute $instance */
            $instance = $attribute->newInstance();
            $instance->withClass($reflectionClass, $apiResource);
            $actions[] = $instance;
        }

        foreach ($reflectionClass->getProperties() as $reflProp){
            foreach ($reflProp->getAttributes(ApiRoute::class,\ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                /** @var ApiRoute $instance */
                $instance = $attribute->newInstance();

                if ($instance instanceof CollectionRoute) {
                    $instance->isItemOperation = true;
                }
                $instance->withProperty($reflProp, $apiResource);
                $actions[] = $instance;
            }
        }

        foreach ($reflectionClass->getMethods() as $reflectionMethod){
            foreach ($reflectionMethod->getAttributes(ApiRoute::class,\ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                /** @var ApiRoute $instance */
                $instance = $attribute->newInstance();

                if ($instance instanceof CollectionRoute) {
                    $instance->isItemOperation = true;
                }

                $instance->withMethod($reflectionMethod,$apiResource);
                $actions[] = $instance;
            }
        }

        return $actions;
    }
}