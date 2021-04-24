<?php


namespace Richard87\ApiRoute\Service;


use Symfony\Bundle\FrameworkBundle\Routing\RouteLoaderInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Config\Loader\Loader;

class ApiLoader extends Loader implements RouteLoaderInterface
{
    private FindClassDescriptors $findClassDescriptors;

    public function __construct(FindClassDescriptors $findClassDescriptors)
    {
        $this->findClassDescriptors = $findClassDescriptors;
    }


    public function load($resource, string $type = null): RouteCollection
    {
        $routeCollection = new RouteCollection();

        $classes = $this->findClassDescriptors->findAttributes($resource);
        foreach ($classes as $class) {
            foreach ($class->actions as $action) {
                $routeCollection->add($action->getPathName(),$action->getRoute());
            }
        }

        return $routeCollection;
    }

    public function supports($resource, string $type = null): bool
    {
        return $type === "api_route";
    }
}