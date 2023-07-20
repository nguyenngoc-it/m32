<?php

namespace Modules\JNTT\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class JNTTServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(JNTTServiceInterface::class, JNTTService::class);
    }

    public function provides()
    {
        return [JNTTServiceInterface::class];
    }
}
