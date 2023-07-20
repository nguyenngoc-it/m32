<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

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

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();

$app->register(Jenssegers\Mongodb\MongodbServiceProvider::class);

$app->withEloquent();

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
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/

$app->configure('activity');
$app->configure('api');
$app->configure('email');
$app->configure('event');
$app->configure('filesystems');
$app->configure('gobiz');
$app->configure('jwt');
$app->configure('kafka');
$app->configure('services');
$app->configure('workflow');
$app->configure('upload');
$app->configure('paginate');
$app->configure('queue');
$app->configure('trustedproxy');
$app->configure('shipping_partner');

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
    App\Http\Middleware\TrustProxies::class,
    App\Http\Middleware\CORS::class,
    App\Http\Middleware\SentryContext::class,
]);

$app->routeMiddleware([
    'auth' => Modules\Auth\Middleware\Authenticate::class,
    'can' => Modules\Auth\Middleware\Authorize::class,
    'can_any' => Modules\Auth\Middleware\Authorize::class.'@any'
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

$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);
$app->register(Sentry\Laravel\ServiceProvider::class);
$app->register(Webklex\PDFMerger\Providers\PDFMergerServiceProvider::class);

$app->register(App\Services\Log\LogServiceProvider::class);
$app->register(App\Providers\RouteBindingServiceProvider::class);
$app->register(App\Providers\DebugServiceProvider::class);
$app->register(Gobiz\Activity\ActivityServiceProvider::class);
$app->register(Gobiz\Kafka\KafkaServiceProvider::class);
$app->register(Gobiz\Log\LogServiceProvider::class);
$app->register(Gobiz\Queue\QueueServiceProvider::class);
$app->register(Gobiz\Email\EmailServiceProvider::class);
$app->register(Gobiz\Event\EventServiceProvider::class);
$app->register(Gobiz\Setting\SettingServiceProvider::class);
$app->register(Gobiz\Transformer\TransformerServiceProvider::class);
$app->register(Gobiz\Workflow\WorkflowServiceProvider::class);

$app->register(Modules\App\Services\AppServiceProvider::class);
$app->register(Modules\Auth\Services\AuthServiceProvider::class);
$app->register(Modules\User\Services\UserServiceProvider::class);
$app->register(Modules\Application\Services\ApplicationServiceProvider::class);
$app->register(Modules\Order\Services\OrderServiceProvider::class);
$app->register(Modules\ShippingPartner\Services\ShippingPartnerServiceProvider::class);
$app->register(Modules\GHN\Services\GHNServiceProvider::class);
$app->register(Modules\SNAPPY\Services\SNAPPYServiceProvider::class);
$app->register(Modules\LWE\Services\LWEServiceProvider::class);
$app->register(Modules\JNTP\Services\JNTPServiceProvider::class);
$app->register(Modules\SHIPPO\Services\SHIPPOServiceProvider::class);
$app->register(Modules\JNTVN\Services\JNTVNServiceProvider::class);
$app->register(Modules\SAPI\Services\SAPIServiceProvider::class);
$app->register(Modules\NIJAVAI\Services\NIJAVAIServiceProvider::class);
$app->register(Modules\NIJAVAM\Services\NIJAVAMServiceProvider::class);
$app->register(Modules\NIJAVAP\Services\NIJAVAPServiceProvider::class);
$app->register(Modules\JNEI\Services\JNEIServiceProvider::class);
$app->register(Modules\JNTT\Services\JNTTServiceProvider::class);
$app->register(Modules\JNTI\Services\JNTIServiceProvider::class);
$app->register(Modules\FLASH\Services\FLASHServiceProvider::class);
$app->register(Modules\JNTM\Services\JNTMServiceProvider::class);
$app->register(Modules\GGE\Services\GGEServiceProvider::class);
$app->register(Modules\JNTC\Services\JNTCServiceProvider::class);

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

$app->router->group([
    'namespace' => 'Modules',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;
