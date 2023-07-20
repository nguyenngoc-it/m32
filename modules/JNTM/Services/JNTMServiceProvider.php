<?php

namespace Modules\JNTM\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class JNTMServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(JNTMServiceInterface::class, JNTMService::class);
    }

    public function provides()
    {
        return [JNTMServiceInterface::class];
    }
}
