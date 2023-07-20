<?php

namespace Modules\Order\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(OrderServiceInterface::class, OrderService::class);
    }

    public function provides()
    {
        return [OrderServiceInterface::class];
    }
}