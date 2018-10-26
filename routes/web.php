<?php

$app->get('/', \App\Controller\HomeController::class . ':home');

$app->group('/demo', \App\Controller\DemoController::class .':routes');

// Using cached before send resposne
$app->add(new \Slim\HttpCache\Cache('public', 3600));

// CROS
$app->add(
    function($request, $response, $next) {
        $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        return $next($request, $response);
    }
);