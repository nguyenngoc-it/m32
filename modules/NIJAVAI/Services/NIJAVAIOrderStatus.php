<?php

namespace Modules\NIJAVAI\Services;

class NIJAVAIOrderStatus
{
    const PICKUP          = 'PICKUP'; // Đơn hàng mới tạo, đang chờ lấy
    const PICKUP_FAILED   = 'PICKUP FAILED'; // Lấy hàng thất bại
    const DEPARTURE       = 'DEPARTURE'; // Nhân viên giao nhận đang đi lấy hàng
    const ARRIVAL         = 'ARRIVAL'; // Nhân viên giao nhận đến nơi lấy hàng
    const DELIVERY        = 'DELIVERY'; // Nhân viên giao nhận đang tương tác với người bán
    const DELIVERING      = 'DELIVERING'; // Nhân viên giao nhận đang giao hàng
    const DELIVERED       = 'DELIVERED'; // Nhân viên giao nhận đã giao hàng
    const DELIVERY_FAILED = 'DELIVERY FAILED'; // Giao hàng không thành công
    const RETURN          = 'RETURN'; // Đang trả hàng
    const RETURNED        = 'RETURNED'; // Đã trả hàng
}
