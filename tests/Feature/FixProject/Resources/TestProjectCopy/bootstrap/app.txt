<?php

use App\Providers\EventServiceProvider;
use Spatie\InteractsWithPayload\InteractsWithPayloadServiceProvider;

$codeCoverageRemote = __DIR__ . '/../c3.php';
if (file_exists($codeCoverageRemote)) {
    include $codeCoverageRemote;
}

require_once __DIR__ . '/../vendor/autoload.php';
/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/
$codeception = isset($_SERVER['HTTP_X_CODECEPTION']);
$env = $codeception ? '.env.testing' : '.env';
(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__),
    $env
))->bootstrap();

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->configure('api');
$app->configure('localization');
$app->withEloquent();
$app->withFacades();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    \App\Http\Middleware\Locale::class,
    'api' => App\Http\Middleware\ApiTrafficLogger::class,
]);
/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/
$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\DingoServiceProvider::class);
$app->register(Illuminate\Database\Eloquent\LegacyFactoryServiceProvider::class);
$app->availableBindings['Illuminate\Auth\AuthManager'] = 'registerAuthBindings';
$app->alias('auth', 'Illuminate\Auth\AuthManager');
$app->register(Pearl\RequestValidate\RequestServiceProvider::class);
$app->register(InteractsWithPayloadServiceProvider::class);
$app->register(EventServiceProvider::class);
/*
|--------------------------------------------------------------------------
| Load Configuration
|--------------------------------------------------------------------------
|
| Load necessary configuration files.
|
*/
$app->configure('graylog');
$app->configure('app');
$app->configure('email');

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/
$app['Dingo\Api\Transformer\Factory']->setAdapter(function ($app) {
    $fractal = new League\Fractal\Manager();
    $baseUrl = getenv('APP_BASEURL');
    $fractal->setSerializer(new League\Fractal\Serializer\JsonApiSerializer($baseUrl));
    return new Dingo\Api\Transformer\Adapter\Fractal($fractal);
});

$app['Dingo\Api\Exception\Handler']->setErrorFormat([
    'errors' => [[
        'status' => ':status_code',
        'code' => ':code',
        'title' => ':message',
        'detail' => ':detail',
        'source' => ':source',
        'debug' => ':debug',
        'test' => ':errors',
    ]],
]);

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router): void {
    require __DIR__ . '/../routes/web.php';
});
return $app;
