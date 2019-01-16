<?php

require __DIR__ . '/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__ . '/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    // Auto catch error by Slim framework
}

$app = new Slim\App([
    'settings' => [
        'isDevelopment' => getenv('APP_DEBUG') === 'true',
        'app' => [
            'name' => getenv('APP_NAME'),
            'version' => getenv('APP_VERSION')
        ],
        'displayErrorDetails' => getenv('APP_DEBUG') === 'true',
        'determineRouteBeforeAppMiddleware' => getenv('DETERMINE_ROUTE_BEFORE_APP_MIDDLEWARE') === 'true',
        'hasCustomHandleError' =>  getenv('HAS_CUSTOM_HANDLE_ERROR') === 'true',
        'useDatabase' => getenv('USE_DATABASE') === 'true',
        'services' => [
            'datetimeFormat' => getenv('DATETIME_FORMAT'),
            'timezone' => getenv('TIMEZONE'),
            'db' => [
                'driver' => getenv('DB_DRIVER'),
                // If you're using phinx, please mapping name database with it
                // Config database name of phinx in phinx.yml
                'database' => getenv('DB_NAME'),
                'host' => getenv('DB_HOST'),
                'port' => getenv('DB_PORT'),
                'username' => getenv('DB_USERNAME'),
                'password' => getenv('DB_PASSWORD'),
                'charset'   => getenv('DB_CHARSET'),
                'collation' => getenv('DB_COLLATION'),
                'prefix'    => getenv('DB_PREFIX_TABLE'),
            ],
            'http' => [
                'proxy' => [
                    'enabled' => false,
                    'host' => '',
                    'port' => '',
                    'username' => '',
                    'password' => ''
                ],
                'timeout' => 30
            ],
            'logger' => [
                'enabled' => getenv('LOGGER_ENABLED') === 'true',
                'logPath' => getenv('LOGGER_PATH') !== '' ? getenv('LOGGER_PATH') : __DIR__ . '/../storage/logs/app.log',
                'logName' => getenv('LOGGER_NAME') !== '' ? getenv('LOGGER_NAME') : 'FromSystem'
            ]
        ]
    ]
]);

$container = $app->getContainer();

if ($container->settings['hasCustomHandleError']) {
    $container['notFoundHandler'] = function () {
        return function ($request, $response) {
            return $response->withStatus(404);
        };
    };

    $container['notAllowedHandler'] = function () {
        return function ($request, $response, $methods) {
            return $response->withStatus(405)->withHeader('Allow', implode(', ', $methods));
        };
    };

    $container['errorHandler'] = function () {
        return function ($request, $response, $exception) {
            $statusCode = $exception->getCode();

            if ($exception->getCode() === 0) {
                $statusCode = 500;
            }
            return $response->withStatus($statusCode);
        };
    };

    $container['phpErrorHandler'] = function () {
        return function ($request, $response, $error) {
            return $response->withStatus(500);
        };
    };
}

if ($container->settings['useDatabase']) {
    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection($container->settings['services']['db']);

    $capsule->setEventDispatcher(
        new \Illuminate\Events\Dispatcher(new \Illuminate\Container\Container)
    );

    $capsule->setAsGlobal();
    $capsule->bootEloquent();
}

$container['logger'] = function ($c) {
    $loggerCfg = $c->settings->services['logger'];

    $logger = new \Monolog\Logger($loggerCfg['logName']);

    if ( $loggerCfg['enabled'] ) {
        $fileHandler = new \Monolog\Handler\StreamHandler($loggerCfg['logPath']);
        $logger->pushHandler($fileHandler);
    }

    return $logger;
};

$container['cacheProvider'] = function () {
    return new \Slim\HttpCache\CacheProvider();
};

require __DIR__ . '/../routes/web.php';
