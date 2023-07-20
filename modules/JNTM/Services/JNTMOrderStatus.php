<?php

namespace Modules\JNTM\Services;

class JNTMOrderStatus
{
    const PICKED_UP        = 'Picked Up'; // Đơn hàng mới tạo, đang chờ lấy
    const DEPARTURE        = 'Departure'; // Trên đường vận chuyển
    const ON_DELIVERY      = 'On Delivery'; // Đang giao hàng
    const ON_HOLD          = 'On Hold'; // Hàng tạm giữ
    const DELIVERED        = 'Delivered'; // Đã giao hàng
    const ON_RETURN        = 'On Return'; // Đang xử lý sự cố
    const RETURN_SIGNATURE = 'Return Signature'; // Đã trả hàng
    const ARRIVED          = 'Arrived'; // Hoàn thành
    const CANCELLED        = 'Cancelled'; // Đã huỷ
}
