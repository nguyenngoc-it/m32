<?php

namespace Modules\JNEI\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class JNEIServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(JNEIServiceInterface::class, JNEIService::class);
    }

    public function provides()
    {
        return [JNEIServiceInterface::class];
    }
}
