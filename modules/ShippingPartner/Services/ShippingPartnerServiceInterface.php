<?php

namespace Modules\ShippingPartner\Services;

use Modules\ShippingPartner\Models\ShippingPartner;

interface ShippingPartnerServiceInterface
{
    /**
     * Lấy danh sách đối tượng đối tác vận chuyển được hỗ trợ
     *
     * @return ShippingPartnerProviderInterface[]
     */
    public function providers();

    /**
     * Lấy đối tượng xử lý việc khai báo thông tin đối tác vận chuyển
     *
     * @param string $code
     * @return ShippingPartnerProviderInterface|null
     */
    public function provider($code);

    /**
     * Lấy đối tượng xử lý tích hợp của đối tác vận chuyển
     *
     * @param ShippingPartner $partner
     * @return ShippingPartnerInterface
     */
    public function partner(ShippingPartner $partner);

    /**
     * @param $applicationId
     * @param $code
     * @return ShippingPartner|null
     */
    public function findByCode($applicationId, $code);

}
