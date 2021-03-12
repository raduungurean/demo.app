<?php

// Define app routes

use App\Action\Home\HomeAction;
use App\Action\Post\PostCreateAction;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Tuupola\Middleware\HttpBasicAuthentication;

return function (App $app) {

    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });

    $app->get('/', HomeAction::class)->setName('home');

    // Password protected area
    $app->group(
        '/api',
        function (RouteCollectorProxy $app) {
            $app->post('/posts', PostCreateAction::class);
        }
    );
};
