<?php

namespace Modules\SAPI\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SAPIServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(SAPIServiceInterface::class, SAPIService::class);
    }

    public function provides()
    {
        return [SAPIServiceInterface::class];
    }
}
