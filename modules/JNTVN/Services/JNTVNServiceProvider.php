<?php

namespace Modules\JNTVN\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class JNTVNServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(JNTVNServiceInterface::class, JNTVNService::class);
    }

    public function provides()
    {
        return [JNTVNServiceInterface::class];
    }
}
