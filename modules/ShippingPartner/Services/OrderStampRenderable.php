<?php

namespace Modules\ShippingPartner\Services;

use Illuminate\Http\Response;
use Modules\Order\Models\Order;

/**
 * Nếu ĐTVC tự in tem trên M28 thì implement interface này
 */
interface OrderStampRenderable
{
    /**
     * Render danh sách tem đơn
     *
     * @param Order[] $orders
     * @return Response
     */
    public function renderOrderStamps(array $orders);
}
