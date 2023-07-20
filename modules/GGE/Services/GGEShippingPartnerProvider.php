<?php

namespace Modules\GGE\Services;

use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerInterface;
use Modules\ShippingPartner\Services\ShippingPartnerProvider;

class GGEShippingPartnerProvider extends ShippingPartnerProvider
{
    /**
     * Tên nhà cung cấp dịch vụ vận chuyển
     *
     * @return string
     */
    public function getName()
    {
        return ShippingPartner::$nameShippingProviders[ShippingPartner::PARTNER_GGE];
    }

    /**
     * Mã nhà cung cấp dịch vụ vận chuyển
     *
     * @return string
     */
    public function getCode()
    {
        return ShippingPartner::PARTNER_GGE;
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
                'name' => 'mchid',
                'type' => 'text',
                'label' => 'Merchant Id',
                'rules' => ['required' => true],
            ],
            [
                'name' => 'key',
                'type' => 'text',
                'label' => 'Key',
                'rules' => ['required' => true],
            ],
            [
                'name' => 'sender',
                'type' => 'form',
                'label' => 'Sender',
                'rules' => ['required' => true],
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
        return new GGEShippingPartner($partner->settings, config('services.gge.api_url'));
    }
}
