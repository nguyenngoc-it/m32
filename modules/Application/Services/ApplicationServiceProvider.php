<?php

namespace Modules\Application\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ApplicationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(ApplicationServiceInterface::class, ApplicationService::class);
    }

    public function provides()
    {
        return [ApplicationServiceInterface::class];
    }
}