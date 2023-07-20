<?php

namespace Modules\LWE\Services;

use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerInterface;
use Modules\ShippingPartner\Services\ShippingPartnerProvider;

class LWEShippingPartnerProvider extends ShippingPartnerProvider
{
    /**
     * Tên nhà cung cấp dịch vụ vận chuyển
     *
     * @return string
     */
    public function getName()
    {
        return ShippingPartner::$nameShippingProviders[ShippingPartner::PARTNER_LWE];
    }

    /**
     * Mã đối tác
     *
     * @return string
     */
    public function getCode()
    {
        return ShippingPartner::PARTNER_LWE;
    }

    /**
     * Mã country code
     *
     * @return string
     */
    public function getCountryCode()
    {
        return Location::COUNTRY_CODE_PHILIPPINES;
    }

    /**
     * Danh sách các params setting
     * VD: $params = [['name' => 'token', 'type' => 'text', 'label' => 'Api Token', 'rules' => ['required'], ...];
     *
     * @return array
     */
    public function getSettingParams()
    {
        return [
            [
                'name' => 'access_token',
                'type' => 'textarea',
                'label' => 'Access Token',
                'rules' => ['required' => true],
            ],
            [
                'name' => 'head_sender',
                'type' => 'text',
                'label' => 'Head Sender',
                'rules' => ['required' => true]
            ],
            [
                'name' => 'sender',
                'type' => 'form',
                'label' => 'Sender',
                'rules' => ['required' => true, 'options' => ['ward_code']],
            ],
        ];
    }

    /**
     * Tạo đối tượng xử lý kết nối
     *
     * @param ShippingPartner $partner
     * @return ShippingPartnerInterface
     */
    public function make(ShippingPartner $partner)
    {
        return new LWEShippingPartner($partner->settings, config('services.lwe.api_url'));
    }
}
