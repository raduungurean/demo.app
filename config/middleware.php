<?php

use App\Middleware\CorsMiddleware;
use App\Middleware\JwtDecodeMiddleware;
use Selective\BasePath\BasePathMiddleware;
use Selective\Validation\Middleware\ValidationExceptionMiddleware;
use Slim\App;
use Slim\Middleware\ErrorMiddleware;

return function (App $app) {
    $container = $app->getContainer();

    $app->addBodyParsingMiddleware();
    $app->add(ValidationExceptionMiddleware::class);
    $app->add(new JwtDecodeMiddleware($container, [ 'refresh' => true ]));
    $app->add(CorsMiddleware::class);
    $app->addRoutingMiddleware();
    $app->add(BasePathMiddleware::class);
    // $app->add(ErrorMiddleware::class);
};
