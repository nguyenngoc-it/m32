<?php

namespace Modules\JNTVN\Services;

class JNTVNOrderStatus
{
    const PICK_UP = 'Pick up'; // Đơn hàng mới tạo, đang chờ lấy
    const DEPARTURE = 'Departure'; // Đơn hàng bị hủy
    const ARRIVAL = 'Arrival'; // Nhân viên giao nhận đang đi lấy hàng
    const DELIVERY = 'Delivery'; // Nhân viên giao nhận đang tương tác với người bán
    const P_O_D = 'P.O.D'; // Nhân viên giao nhận đã lấy hàng từ người bán
    const RETURN = 'Return'; // Đơn hàng đang được lưu kho giao nhận/bưu cục
    const R_P_O_D = 'R.P.O.D'; // Đơn hàng đang được luân chuyển
}
