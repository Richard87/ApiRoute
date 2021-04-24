<?php


namespace Richard87\ApiRouteBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder("api_route");
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->scalarNode("base_path")->defaultValue("/api")->end()
            ->booleanNode("swagger")->defaultTrue()->end()
            ->arrayNode("resources")
                ->info("List of paths to find ApiRoute annotations")
                ->defaultValue(["../src/"])
                ->scalarPrototype()->end()
                ->end()
            ->arrayNode("openapi")
                ->info("OpenAPI Information")
                ->children()
                    ->scalarNode("title")->defaultValue("ApiRoute Example")->end()
                    ->scalarNode("version")->defaultValue("1.0.0")->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}