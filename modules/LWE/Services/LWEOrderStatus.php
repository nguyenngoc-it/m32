<?php

namespace Modules\LWE\Services;

class LWEOrderStatus
{
    const READY_FOR_PICKUP = '3'; // Đơn mới
    const RECEIVED_AT_DESTINATION = '7'; // Đã lấy
    const OUT_FOR_DELIVERY = '10'; // Đang giao
    const DELIVERED = '11'; // Giao thành công
    const CANCELED = '31'; // Đã hủy
    const RETURNING_39 = '39'; // Đang trả hàng
    const RETURNING_42 = '42'; // Đang trả hàng

    const ERROR_14 = '14'; // Lỗi
    const ERROR_20 = '20'; // Lỗi
    const ERROR_22 = '22'; // Lỗi
    const ERROR_23 = '23'; // Lỗi
    const ERROR_26 = '26'; // Lỗi
    const ERROR_27 = '27'; // Lỗi
    const ERROR_28 = '28'; // Lỗi
    const ERROR_29 = '29'; // Lỗi
    const ERROR_30 = '30'; // Lỗi
    const ERROR_36 = '36'; // Lỗi
}
