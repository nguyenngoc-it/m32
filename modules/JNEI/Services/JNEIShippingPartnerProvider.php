<?php

namespace Modules\JNEI\Services;

use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerInterface;
use Modules\ShippingPartner\Services\ShippingPartnerProvider;

/**
 * https://apidash.jne.co.id/home
 *
 * Class JNEIShippingPartnerProvider
 * @package Modules\JNEI\Services
 */
class JNEIShippingPartnerProvider extends ShippingPartnerProvider
{
    /**
     * Tên nhà cung cấp dịch vụ vận chuyển
     *
     * @return string
     */
    public function getName()
    {
        return ShippingPartner::$nameShippingProviders[ShippingPartner::PARTNER_JNEI];
    }

    /**
     * Mã nhà cung cấp dịch vụ vận chuyển
     *
     * @return string
     */
    public function getCode()
    {
        return ShippingPartner::PARTNER_JNEI;
    }

    /**
     * Mã country code
     *
     * @return string
     */
    public function getCountryCode()
    {
        return Location::COUNTRY_CODE_INDONESIA;
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
                'name' => 'username',
                'type' => 'text',
                'label' => 'Username',
                'rules' => ['required' => true],
            ],
            [
                'name' => 'api_key',
                'type' => 'text',
                'label' => 'Api Key',
                'rules' => ['required' => true],
            ],
            [
                'name' => 'olshop_branch',
                'type' => 'text',
                'label' => 'OLSHOP BRANCH',
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
        return new JNEIShippingPartner($partner->settings, config('services.jnei.api_url'));
    }
}
