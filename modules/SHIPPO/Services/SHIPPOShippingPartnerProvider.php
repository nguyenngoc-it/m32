<?php

namespace Modules\SHIPPO\Services;

use GuzzleHttp\Exception\GuzzleException;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerInterface;
use Modules\ShippingPartner\Services\ShippingPartnerProvider;

class SHIPPOShippingPartnerProvider extends ShippingPartnerProvider
{
    /**
     * Tên nhà cung cấp dịch vụ vận chuyển
     *
     * @return string
     */
    public function getName()
    {
        return ShippingPartner::$nameShippingProviders[ShippingPartner::PARTNER_SHIPPO];
    }

    /**
     * Mã nhà cung cấp dịch vụ vận chuyển
     *
     * @return string
     */
    public function getCode()
    {
        return ShippingPartner::PARTNER_SHIPPO;
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
                'name' => 'base_url',
                'type' => 'text',
                'label' => 'Website',
                'rules' => ['required' => true],
            ],
            [
                'name' => 'client_id',
                'type' => 'hidden',
                'label' => 'Client Id',
                'rules' => ['required' => false],
            ],
            [
                'name' => 'username',
                'type' => 'text',
                'label' => 'Tên đăng nhập',
                'rules' => ['required' => true],
            ],
            [
                'name' => 'password',
                'type' => 'password',
                'label' => 'Mật khẩu',
                'rules' => ['required' => true],
            ],
            [
                'name' => 'token',
                'type' => 'hidden',
                'label' => 'Token',
                'rules' => ['required' => false],
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
     * @throws GuzzleException
     * @throws ShippingPartnerException
     */
    public function make(ShippingPartner $partner)
    {
        return new SHIPPOShippingPartner($partner, config('services.shippo.api_url'));
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
