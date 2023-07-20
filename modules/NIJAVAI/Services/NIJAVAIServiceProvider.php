<?php

namespace Modules\NIJAVAI\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class NIJAVAIServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(NIJAVAIServiceInterface::class, NIJAVAIService::class);
    }

    public function provides()
    {
        return [NIJAVAIServiceInterface::class];
    }
}
