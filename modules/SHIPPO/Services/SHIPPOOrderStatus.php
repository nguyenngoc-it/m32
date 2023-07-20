<?php

namespace Modules\SHIPPO\Services;

class SHIPPOOrderStatus
{
    const PENDING             = 'PENDING'; // Chờ duyệt
    const ACCEPTED            = 'ACCEPTED'; // Đã ký gửi
    const MERCHANT_DELIVERING = 'MERCHANT_DELIVERING'; // Người bán giao
    const PUTAWAY             = 'PUTAWAY'; // Hàng về kho
    const TRANSPORTING        = 'TRANSPORTING'; // Vận chuyển quốc tế
    const READY_FOR_DELIVERY  = 'READY_FOR_DELIVERY'; // Chờ giao
    const DELIVERING          = 'DELIVERING'; // Đang giao
    const DELIVERED           = 'DELIVERED'; // Đã nhận
    const CANCELLED           = 'CANCELLED'; // Huỷ bỏ
    const MIA                 = 'MIA'; // Thất lạc
    const DELIVERY_CANCELLED  = 'DELIVERY_CANCELLED'; // Khách từ chối nhận hàng
}
