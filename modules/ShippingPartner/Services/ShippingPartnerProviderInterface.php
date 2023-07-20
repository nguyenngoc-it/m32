<?php

namespace Modules\ShippingPartner\Services;

use Illuminate\Contracts\Validation\Validator;
use Modules\ShippingPartner\Models\ShippingPartner;

/**
 * Interface define các thông tin mà 1 đối tác kết nối vận chuyển phải cung cấp để tích hợp với hệ thống
 */
interface ShippingPartnerProviderInterface
{
    /**
     * Tên nhà cung cấp dịch vụ vận chuyển
     *
     * @return string
     */
    public function getName();

    /**
     * Mã nhà cung cấp dịch vụ vận chuyển
     *
     * @return string
     */
    public function getCode();

    /**
     * Mã country code
     *
     * @return string
     */
    public function getCountryCode();

    /**
     * Danh sách các params setting
     * VD: $params = [['name' => 'token', 'type' => 'text', 'label' => 'Api Token', 'rules' => ['required'], ...];
     *
     * @return array
     */
    public function getSettingParams();

    /**
     * Validate settings
     *
     * @param array $settings
     * @return Validator
     */
    public function validateSettings(array $settings);

    /**
     * Tạo đối tượng xử lý kết nối
     *
     * @param ShippingPartner $partner
     * @return ShippingPartnerInterface
     */
    public function make(ShippingPartner $partner);
}
