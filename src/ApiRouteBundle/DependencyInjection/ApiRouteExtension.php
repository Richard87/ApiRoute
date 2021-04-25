<?php


namespace Richard87\ApiRouteBundle\DependencyInjection;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ApiRouteExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . "/../Resource/config"));
        $loader->load("services.xml");

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $definition = $container->getDefinition("api_route.openapi_generator");
        $definition->setArgument('$info', $config['openapi']);

        $definition = $container->getDefinition("api_route.api_route_loader");
        $definition->setArgument('$basePath', $config['base_path']);
        $definition->setArgument('$enableSwagger', $config['enable_swagger']);
        $definition->setArgument('$projectDir', $container->getParameter("kernel.project_dir"));
        // $definition->addTag("routing.loader");
    }
}