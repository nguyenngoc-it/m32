<?php

namespace Modules\JNTVN\Services;

use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerInterface;
use Modules\ShippingPartner\Services\ShippingPartnerProvider;

class JNTVNShippingPartnerProvider extends ShippingPartnerProvider
{
    /**
     * Tên nhà cung cấp dịch vụ vận chuyển
     *
     * @return string
     */
    public function getName()
    {
        return ShippingPartner::$nameShippingProviders[ShippingPartner::PARTNER_JNTVN];
    }

    /**
     * Mã nhà cung cấp dịch vụ vận chuyển
     *
     * @return string
     */
    public function getCode()
    {
        return ShippingPartner::PARTNER_JNTVN;
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
                'name' => 'eccompanyid',
                'type' => 'text',
                'label' => 'ECCompany id',
                'rules' => ['required' => true],
            ],
            [
                'name' => 'key',
                'type' => 'text',
                'label' => 'Key',
                'rules' => ['required' => true],
            ],
            [
                'name' => 'customerid',
                'type' => 'text',
                'label' => 'Customer id',
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
        return new JNTVNShippingPartner($partner->settings, config('services.jntvn.api_url'));
    }

    /**
     * Mã country code
     *
     * @return string
     */
    public function getCountryCode()
    {
        return Location::COUNTRY_CODE_VIETNAM;
    }
}
