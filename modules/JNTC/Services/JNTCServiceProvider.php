<?php

namespace Modules\JNTC\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class JNTCServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(JNTCServiceInterface::class, JNTCService::class);
    }

    public function provides()
    {
        return [JNTCServiceInterface::class];
    }
}
