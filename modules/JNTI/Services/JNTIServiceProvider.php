<?php

namespace Modules\JNTI\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class JNTIServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(JNTIServiceInterface::class, JNTIService::class);
    }

    public function provides()
    {
        return [JNTIServiceInterface::class];
    }
}
