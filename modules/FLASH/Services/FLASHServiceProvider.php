<?php

namespace Modules\FLASH\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class FLASHServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(FLASHServiceInterface::class, FLASHService::class);
    }

    public function provides()
    {
        return [FLASHServiceInterface::class];
    }
}
