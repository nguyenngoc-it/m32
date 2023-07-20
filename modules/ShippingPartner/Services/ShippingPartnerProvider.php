<?php

namespace Modules\ShippingPartner\Services;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;

abstract class ShippingPartnerProvider implements ShippingPartnerProviderInterface
{
    /**
     * Validate settings
     *
     * @param array $settings
     * @return Validator
     */
    public function validateSettings(array $settings)
    {
        return validator($settings, Arr::pluck($this->getSettingParams(), 'rules', 'name'));
    }
}