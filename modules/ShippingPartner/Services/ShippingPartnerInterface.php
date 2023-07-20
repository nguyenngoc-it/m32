<?php

namespace Modules\ShippingPartner\Services;

use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;

/**
 * Interface dành cho các đối tượng xử lý kết nối api đến đối tác vận chuyển
 *
 * Vì thực tế hệ thống có thể:
 * - Tích hợp trực tiếp với các đơn vị vận chuyển (VD Giao Hàng Nhanh, Giao Hàng Tiết Kiệm, ...)
 * - Tích hợp với các đơn vị trung gian vận chuyển (VD: Goship, ...)
 *
 * Nên để thống nhất thì sẽ quy định như sau:
 * - ShippingPartner là các đối tác tích hợp vận chuyển (code lấy theo constance ShippingPartner::PARTNER_*)
 * - ShippingCarrier là các đơn vị vận chuyển thực tế đươc hỗ trợ bởi ShippingPartner (code lấy theo constance ShippingPartner::CARRIER_*)
 *
 * VD khi tích hợp GHN thì: ShippingPartner = GHN, ShippingCarriers = [GHN]
 * khi tích hợp Goship thì: ShippingPartner = GOSHIP, ShippingCarriers = [GHN, GHTK, ...]
 */
interface ShippingPartnerInterface
{
    /**
     * Test kết nối để check thông tin config có đúng không
     *
     * @throws ShippingPartnerException
     */
    public function test();

    /**
     * Lấy danh sách các đơn vị vận chuyển được hỗ trợ
     *
     * @return array
     */
    public function getShippingCarriers();

    /**
     * Tạo đơn vận chuyển
     *
     * @param Order|null $order
     * @return ShippingPartnerOrder
     * @throws ShippingPartnerException
     */
    public function createOrder(Order $order);

    /**
     * Tính phí VC
     *
     * @param Location $senderWard
     * @param Location $receiveWard
     * @param ShippingPartnerSize $shippingPartnerSize
     * @return float|null
     * @throws ShippingPartnerException
     */
    public function shippingFee(Location $senderWard, Location $receiveWard, ShippingPartnerSize $shippingPartnerSize);

    /**
     * Lấy thông tin đơn từ bên DVVC
     *
     * @param Order $order
     * @return ShippingPartnerOrder
     * @throws ShippingPartnerException
     */
    public function getOrderInfo(Order $order);

    /**
     * Lấy thông tin trackings từ DVVC
     *
     * @param array $trackings
     * @return Tracking[]|array
     */
    public function getTrackings(array $trackings);

    /**
     * Lấy url tem của danh sách đơn
     *
     * @param Order[] $orders
     * @return string|null
     * @throws ShippingPartnerException
     */
    public function getOrderStampsUrl(array $orders);
}
