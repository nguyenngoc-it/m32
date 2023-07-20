<?php

namespace Modules\GGE\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class GGEServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(GGEServiceInterface::class, GGEService::class);
    }

    public function provides()
    {
        return [GGEServiceInterface::class];
    }
}
