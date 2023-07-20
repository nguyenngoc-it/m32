<?php
/** @var Router $router */

use Laravel\Lumen\Routing\Router;
use Modules\Application\Middleware\AuthenticateApplication;
use Modules\Auth\Services\Permission;
use Modules\User\Models\User;

$router->options('{any:.*}', function () {
    return '';
});

$router->get('/', function () use ($router) {
    return response()->json(['service' => 'm32']);
});
$router->get('metrics', 'App\Controllers\AppController@metrics');
$router->get('order-stamps', 'Order\Controllers\OrderController@stamps');

/*
 * Admin api router
 */
$router->group([
    'prefix' => 'admin',
], function () use ($router) {

    /**
     * tools api
     */
    $router->group([
        'middleware' => [
            'auth',
            'can:' . Permission::TOOLS
        ]
    ], function () use ($router) {
        $router->group([
            'prefix' => 'tools/',
            'namespace' => 'Tools\Controllers',
        ], function () use ($router) {
            $router->post('webhook-emulator', 'ManualToolsController@webhookEmulator');
            $router->post('tracking-statistics', 'ManualToolsController@trackingStatistic');
            $router->post('sync-location-mappings', 'ManualToolsController@syncLocationMapping');
        });
    });

    $router->get('login', 'Auth\Controllers\AuthController@login');
    $router->get('login/callback', 'Auth\Controllers\AuthController@loginCallback');

    $router->group([
        'middleware' => [
            'auth:api',
        ],
    ], function () use ($router) {
        $router->group([
            'prefix' => 'auth',
            'namespace' => 'Auth\Controllers',
        ], function () use ($router) {
            $router->get('user', 'AuthController@user');
        });

        $router->group([
            'prefix' => 'applications',
            'namespace' => 'Application\Controllers',
        ], function () use ($router) {
            $router->get('/', 'ApplicationController@index');
            $router->post('/', 'ApplicationController@create');
            $router->get('/order-status', 'OrderController@listStatus');

            $router->group([
                'prefix' => '{application}',
                'middleware' => ['can:' . Permission::VIEW_APPLICATION . ',application']
            ], function () use ($router) {
                $router->get('/secret', 'ApplicationController@getSecret');
                $router->get('/', 'ApplicationController@detail');
                $router->get('/shipping-partners', 'ApplicationController@listingShippingPartner');
                $router->post('/shipping-partners', 'ApplicationController@createShippingPartner');
                $router->put('/whitelist-ip', 'ApplicationController@whitelistIp');
                $router->get('/orders', '\Modules\Order\Controllers\OrderController@index');
                $router->put('/webhook-url', 'ApplicationController@webhookUrl');

                $router->group([
                    'prefix' => 'members'
                ], function () use ($router) {
                    $router->get('/', 'ApplicationMemberController@index');
                    $router->post('/', [
                        'middleware' => ['can:' . Permission::ADD_APPLICATION_MEMBER . ',application'],
                        'uses' => 'ApplicationMemberController@addMember'
                    ]);
                    $router->delete('/{member_id}', [
                        'middleware' => ['can:' . Permission::REMOVE_APPLICATION_MEMBER . ',application'],
                        'uses' => 'ApplicationMemberController@remove'
                    ]);
                });
            });
        });

        $router->group([
            'prefix' => 'shipping-providers',
            'namespace' => 'ShippingPartner\Controllers',
        ], function () use ($router) {
            $router->get('/', 'ShippingProviderController@listProviders');
        });

        $router->group([
            'prefix' => 'locations',
            'namespace' => 'Location\Controllers',
        ], function () use ($router) {
            $router->get('/', 'LocationController@index');
        });

    });
});

/*
 * Application api router
 */
$router->group([
    'prefix' => 'application',
], function () use ($router) {
    $router->post('access-tokens', 'Application\Controllers\ApplicationApiController@createToken');

    $router->group([
        'middleware' => [
            AuthenticateApplication::class,
        ],
    ], function () use ($router) {
        $router->get('/', function () use ($router) {
            /** @var User $user */
            $user = auth('application')->user();
            return response()->json(['application' => $user->only(['id', 'name', 'description'])]);
        });

        $router->post('orders', 'Order\Controllers\OrderApplicationApiController@create');
        $router->post('orders/mapping-tracking', 'Order\Controllers\OrderApplicationApiController@mappingTracking');
        $router->get('shipping-partners/{code}/stamps', 'Order\Controllers\OrderApplicationApiController@getStampsUrl');
        $router->get('shipping-partners/{code}/fee', 'ShippingPartner\Controllers\ShippingPartnerApplicationApiController@shippingFee');
    });
});

/*
 * Webhook router
 */
$router->group([
    'prefix' => 'webhook',
], function () use ($router) {
    $router->post('ghn', [
        'uses' => 'GHN\Controllers\GHNController@webhook',
        'middleware' => ['can:' . Permission::WEBHOOK_GHN],
    ]);

    $router->group(
        ['prefix' => 'snappy']
        , function () use ($router) {
        $router->post('/', [
            'uses' => 'SNAPPY\Controllers\SNAPPYController@webhook',
            'middleware' => ['can:' . Permission::WEBHOOK_SNAPPY],
        ]);

        $router->post('/register', [
            'uses' => 'SNAPPY\Controllers\SNAPPYController@webhookRegister',
            'middleware' => ['can:' . Permission::WEBHOOK_SNAPPY],
        ]);
    });

    $router->post('lwe', [
        'uses' => 'LWE\Controllers\LWEController@webhook',
        'middleware' => ['can:' . Permission::WEBHOOK_LWE],
    ]);
    $router->post('jntp', [
        'uses' => 'JNTP\Controllers\JNTPController@webhook',
        'middleware' => ['can:' . Permission::WEBHOOK_JNTP],
    ]);
    $router->post('jntvn', [
        'uses' => 'JNTVN\Controllers\JNTVNController@webhook',
        'middleware' => ['can:' . Permission::WEBHOOK_JNTVN],
    ]);
    $router->post('sapi', [
        'uses' => 'SAPI\Controllers\SAPIController@webhook',
        'middleware' => ['can:' . Permission::WEBHOOK_SAPI],
    ]);
    $router->post('nijavai', [
        'uses' => 'NIJAVAI\Controllers\SAPIController@webhook',
        'middleware' => ['can:' . Permission::WEBHOOK_NIJAVAI],
    ]);
    $router->post('nijavam/{appId}/{shippingPartnerCode}', [
        'uses' => 'NIJAVAM\Controllers\NIJAVAMController@webhook',
    ]);
    $router->post('nijavap/{appId}/{shippingPartnerCode}', [
        'uses' => 'NIJAVAP\Controllers\NIJAVAPController@webhook',
    ]);
    $router->post('jntc', [
        'uses' => 'JNTC\Controllers\JNTCController@webhook',
        'middleware' => ['can:' . Permission::WEBHOOK_JNTC],
    ]);

    $router->group(
        ['prefix' => 'flash']
        , function () use ($router) {
        $router->post('/', [
            'uses' => 'FLASH\Controllers\FlashController@webhook',
            'middleware' => ['can:' . Permission::WEBHOOK_FLASH],
        ]);

        $router->post('/register', [
            'uses' => 'FLASH\Controllers\FlashController@webhookRegister',
            'middleware' => ['can:' . Permission::WEBHOOK_FLASH],
        ]);
    });
});
