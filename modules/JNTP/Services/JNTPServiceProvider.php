<?php

namespace Modules\JNTP\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class JNTPServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(JNTPServiceInterface::class, JNTPService::class);
    }

    public function provides()
    {
        return [JNTPServiceInterface::class];
    }
}
