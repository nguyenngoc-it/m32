<?php

namespace Modules\GHN\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class GHNServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(GHNServiceInterface::class, GHNService::class);
    }

    public function provides()
    {
        return [GHNServiceInterface::class];
    }
}