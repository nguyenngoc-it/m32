<?php

namespace Modules\FLASH\Services;

class FLASHOrderStatus
{
    const PICKUP                 = 1; // Đơn hàng mới tạo, đang chờ lấy
    const IN_TRANSIT             = 2; // Trên đường vận chuyển
    const ON_DELIVERY            = 3; // Đang giao hàng
    const DETAINED               = 4; // Hàng tạm giữ
    const DELIVERED              = 5; // Đã giao hàng
    const PROBLEMATIC_PROCESSING = 6; // Đang xử lý sự cố
    const RETURNED               = 7; // Đã trả hàng
    const CLOSED                 = 8; // Hoàn thành
    const CANCELLED              = 9; // Đã huỷ
}
