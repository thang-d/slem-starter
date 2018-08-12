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

        'determineRouteBeforeAppMiddleware' => getenv('DETERMINE_ROUTE_BEFORE_APP_MIDDLEWARE') === 'true',

        'app' => [
            'name' => getenv('APP_NAME'),
            'version' => getenv('APP_VERSION')
        ],

        'database' => [
            'name' => getenv('DB_NAME'),
            'host' => getenv('DB_HOST'),
            'port' => getenv('DB_PORT'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD')
        ],

        'datetimeFormat' => 'Y-m-d H:i:s',

        'timezone' => 'Asia/Ho_Chi_Minh',

        'views' => [
            'cache' => getenv('VIEW_CACHE_DISABLED') === 'true' ? false : __DIR__ . '/../storage/views',
            'compress' => getenv('VIEW_COMPRESS_ENABLED') === 'true' ? true : false
        ],

        'logger' => [
            'enabled' => getenv('LOGGER_ENABLED') === 'true' ? true : false,
            'logDir' => getenv('LOGGER_DIR') !== '' ? getenv('LOGGER_DIR') : __DIR__ . '/../storage/log/app.log',
            'logName' => getenv('LOGGER_NAME') !== '' ? getenv('LOGGER_NAME') : 'FromSystem'
        ],

        'canCustomHandleError' => getenv('CAN_CUSTOM_HANDLE_ERROR') === 'true' ? true : false,
        'canUseDatabase' => getenv('CAN_USE_DATABASE')
    ]
]);

$container = $app->getContainer();

$container['logger'] = function($container) {
    $logger = new \Monolog\Logger(
        $container->settings['logger']['logName']
    );

    if ( $container->settings['logger']['enabled'] ) {
        $file_handler = new \Monolog\Handler\StreamHandler(
            $container->settings['logger']['logDir']
        );
        $logger->pushHandler($file_handler);
    }

    return $logger;
};

$container['flash'] = function ($c) {
    session_start();
    return new \Slim\Flash\Messages();
};

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

/*$container['php_view'] = function($c) {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../resources/views');
};*/

if ($container->settings['canUseDatabase']) {
    $container['db'] = function ($container) {
        $db = $container->settings['database'];
        $pdo = new \PDO(
            "mysql:host=" . $db['host'] . ";dbname=" . $db['name'],
            $db['username'],
            $db['password']
        );
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        return $pdo;
    };
}

if ($container->settings['canCustomHandleError']) {
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

require_once __DIR__ . '/../routes/web.php';
