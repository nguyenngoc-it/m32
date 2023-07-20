<?php /** @noinspection ALL */

namespace Modules\NIJAVAP\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class NIJAVAPServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(NIJAVAPServiceInterface::class, NIJAVAPService::class);
    }

    public function provides()
    {
        return [NIJAVAPServiceInterface::class];
    }
}
