<?php /** @noinspection ALL */

namespace Modules\NIJAVAM\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class NIJAVAMServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(NIJAVAMServiceInterface::class, NIJAVAMService::class);
    }

    public function provides()
    {
        return [NIJAVAMServiceInterface::class];
    }
}
