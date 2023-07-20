<?php

namespace Modules\SNAPPY\Services;

use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerInterface;
use Modules\ShippingPartner\Services\ShippingPartnerProvider;

class SNAPPYShippingPartnerProvider extends ShippingPartnerProvider
{
    /**
     * Tên nhà cung cấp dịch vụ vận chuyển
     *
     * @return string
     */
    public function getName(): string
    {
        return ShippingPartner::$nameShippingProviders[ShippingPartner::PARTNER_SNAPPY];
    }

    /**
     * Mã country code
     *
     * @return string
     */
    public function getCountryCode(): string
    {
        return Location::COUNTRY_CODE_VIETNAM;
    }

    /**
     * Mã đối tác
     *
     * @return string
     */
    public function getCode(): string
    {
        return ShippingPartner::PARTNER_SNAPPY;
    }

    /**
     * Danh sách các params setting
     * VD: $params = [['name' => 'token', 'type' => 'text', 'label' => 'Api Token', 'rules' => ['required'], ...];
     *
     * @return array
     */
    public function getSettingParams(): array
    {
        return [
            [
                'name' => 'access_token',
                'type' => 'textarea',
                'label' => 'Access Token',
                'rules' => ['required' => true],
            ],
            [
                'name' => 'business_id',
                'type' => 'text',
                'label' => 'Business ID',
                'rules' => ['required' => true],
            ],
            [
                'name' => 'business_address_id',
                'type' => 'text',
                'label' => 'Business address ID',
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
        return new SNAPPYShippingPartner($partner->settings, config('services.snappy.api_url'));
    }
}
