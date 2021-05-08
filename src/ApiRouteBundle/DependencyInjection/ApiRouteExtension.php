<?php


namespace Richard87\ApiRouteBundle\DependencyInjection;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ApiRouteExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . "/../Resource/config"));
        $loader->load("services.xml");

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $basePath = $config['base_path'];
        if (str_ends_with($basePath,"/")) {
            $basePath = substr($basePath, 0, -1);
        }
        $docsEndpoint = $basePath . "/docs.json";

        $definition = $container->getDefinition("api_route.openapi_generator");
        $definition->setArgument('$info', $config['openapi']);
        $definition->setArgument('$basePath', $basePath);

        $definition = $container->getDefinition("api_route.api_route_loader");
        $definition->setArgument('$basePath', $basePath);
        $definition->setArgument('$docsEndpoint', $docsEndpoint);
        $definition->setArgument('$enableSwagger', $config['enable_swagger']);
        $definition->setArgument('$projectDir', $container->getParameter("kernel.project_dir"));

        $definition = $container->getDefinition("api_route.swagger_controller");
        $definition->setArgument('$docsEndpoint', $docsEndpoint);
        // $definition->addTag("routing.loader");
    }
}