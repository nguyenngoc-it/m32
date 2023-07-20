<?php

namespace Modules\SHIPPO\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SHIPPOServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(SHIPPOServiceInterface::class, SHIPPOService::class);
    }

    public function provides()
    {
        return [SHIPPOServiceInterface::class];
    }
}
