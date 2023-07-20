<?php

namespace Modules\ShippingPartner\Transformers;

use Gobiz\Transformer\TransformerInterface;
use Modules\ShippingPartner\Services\ShippingPartnerProviderInterface;

class ShippingPartnerProviderTransformer implements TransformerInterface
{
    /**
     * Transform the data
     *
     * @param ShippingPartnerProviderInterface $provider
     * @return mixed
     */
    public function transform($provider)
    {
        return [
            'code' => $provider->getCode(),
            'name' => $provider->getName(),
            'country_code' => $provider->getCountryCode(),
            'setting_params' => $provider->getSettingParams(),
        ];
    }
}
