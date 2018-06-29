<?php

require_once __DIR__ . '/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__ . '/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

$app = new Slim\App([
    'settings' => [
        'displayErrorDetails' => getenv('APP_DEBUG') === 'true',

        'app' => [
            'name' => getenv('APP_NAME'),
            'version' => getenv('APP_VERSION')
        ],

        'views' => [
            'cache' => getenv('VIEW_CACHE_DISABLED') === 'true' ? false : __DIR__ . '/../storage/views',
            'compress' => getenv('VIEW_COMPRESS_ENABLED') === 'true' ? true : false
        ],

        'flashEnabled' => getenv('FLASH_MESSAGE_ENABLED') === 'true' ? true : false,

        'logger' => [
            'enabled' => getenv('LOGGER_ENABLED') === 'true' ? true : false,
            'logDir' => getenv('LOGGER_DIR') !== '' ? getenv('LOGGER_DIR') : __DIR__ . '/../storage/log/app.log',
            'logName' => getenv('LOGGER_NAME') !== '' ? getenv('LOGGER_NAME') : 'FromSystem'
        ],

        'customHandleErrorEnabled' => getenv('CUSTOM_HANDLE_ERROR_ENABLED') === 'true' ? true : false
    ],
]);

$container = $app->getContainer();

if ( $container->settings['logger']['enabled'] ) {
    $container['logger'] = function($container) {
        $logger = new \Monolog\Logger(
            $container->settings['logger']['logName']
        );
        $file_handler = new \Monolog\Handler\StreamHandler(
            $container->settings['logger']['logDir']
        );
        $logger->pushHandler($file_handler);

        return $logger;
    };
}

if ( $container->settings['flashEnabled'] ) {
    $container['flash'] = function ($c) {
        session_start();
        return new \Slim\Flash\Messages();
    };
}

if ( $container->settings['customHandleErrorEnabled'] ) {
    $container['notFoundHandler'] = function ($container) {
        return function ($request, $response) use ($container) {
            return $container->get('response')->withStatus(404);
        };
    };

    $container['notAllowedHandler'] = function ($container) {
        return function ($request, $response, $methods) use ($container) {
            return $container->get('response')
                ->withStatus(405)
                ->withHeader('Allow', implode(', ', $methods));
        };
    };

    $container['errorHandler'] = function ($container) {
        return function ($request, $response, $exception) use ($container) {
            $statusCode = $exception->getCode();

            if ($exception->getCode() === 0) {
                $statusCode = 500;
            }
            return $container->get('response')
                    ->withStatus($statusCode);
        };
    };

    $container['phpErrorHandler'] = function ($container) {
        return function ($request, $response, $error) use ($container) {
            return $container->get('response')->withStatus(500);
        };
    };
}

$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig(__DIR__ . '/../resources/views', [
        'cache' => $container->settings['views']['cache']
    ]);

    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));

    if ( $container->settings['views']['compress'] ) {
        $view->addExtension(new \nochso\HtmlCompressTwig\Extension());
    }

    return $view;
};

require_once __DIR__ . '/../routes/web.php';
