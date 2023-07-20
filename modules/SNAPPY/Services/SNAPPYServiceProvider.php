<?php

namespace Modules\SNAPPY\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SNAPPYServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(SNAPPYServiceInterface::class, SNAPPYService::class);
    }

    public function provides()
    {
        return [SNAPPYServiceInterface::class];
    }
}