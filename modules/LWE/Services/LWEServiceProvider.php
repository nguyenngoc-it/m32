<?php

namespace Modules\LWE\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class LWEServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(LWEServiceInterface::class, LWEService::class);
    }

    public function provides()
    {
        return [LWEServiceInterface::class];
    }
}