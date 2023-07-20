<?php

namespace Modules\JNTT\Services;

class JNTTOrderStatus
{
    const PICKUP              = 'Picked Up'; // Đơn hàng mới tạo, đang chờ lấy
    const DEPARTURE           = 'Departure'; // Nhân viên giao nhận đang đi lấy hàng
    const ARRIVAL             = 'Arrival'; // Nhân viên giao nhận đến nơi lấy hàng
    const ON_DELIVERY         = 'On Delivery'; // Nhân viên giao nhận đang tương tác với người bán
    const SELF_COLLECTION     = 'Self Collection'; // Nhân viên giao nhận giao hàng tận nơi
    const SIGNATURE           = 'Signature'; // Ký nhận giao hàng thành công
    const RETURN              = 'Return'; // Phải trả hàng
    const RETURN_CONFIRMATION = 'Return Confirmation'; // Xác nhận trả hàng
    const RETURN_SIGNATURE    = 'Return Signature'; // Ký nhận trả hàng
    const PROBLEMATIC         = 'Problematic'; // Có vấn đề
    const Storage             = 'Storage'; // Lưu kho
}
