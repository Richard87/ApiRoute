<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes->import("src/Controller", "api_route");
    $routes->import("src/Entity", "api_route");
};
