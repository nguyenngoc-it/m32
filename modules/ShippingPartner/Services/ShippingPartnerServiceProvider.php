<?php

namespace Modules\ShippingPartner\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ShippingPartnerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(ShippingPartnerServiceInterface::class, function () {
            $providers = $this->makeShippingPartnerProviders();

            return new ShippingPartnerService($providers);
        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return [ShippingPartnerServiceInterface::class];
    }

    /**
     * @return ShippingPartnerProviderInterface[]
     */
    protected function makeShippingPartnerProviders()
    {
        return array_map(function ($providerClass) {
            return $this->app->make($providerClass);
        }, config('shipping_partner.providers'));
    }
}
