<?php

namespace Modules\GHN\Services;

class GHNOrderStatus
{
    const READY_TO_PICK = 'ready_to_pick'; // Đơn hàng mới tạo, đang chờ lấy
    const CANCEL = 'cancel'; // Đơn hàng bị hủy
    const PICKING = 'picking'; // Nhân viên giao nhận đang đi lấy hàng
    const MONEY_COLLECT_PICKING = 'money_collect_picking'; // Nhân viên giao nhận đang tương tác với người bán
    const PICKED = 'picked'; // Nhân viên giao nhận đã lấy hàng từ người bán
    const STORING = 'storing'; // Đơn hàng đang được lưu kho giao nhận/bưu cục
    const TRANSPORTING = 'transporting'; // Đơn hàng đang được luân chuyển
    const SORTING = 'sorting'; // Đơn hàng đang được phân loại (tại Kho phân loại hàng)
    const DELIVERING = 'delivering'; // Nhân viên giao nhận đang đi giao hàng
    const MONEY_COLLECT_DELIVERING = 'money_collect_delivering'; // Nhân viên giao nhận đang tương tác với người mua
    const DELIVERED = 'delivered'; // Đơn hàng đã được giao cho người mua
    const DELIVERY_FAIL = 'delivery_fail'; // Đơn hàng giao thất bại
    const WAITING_TO_RETURN = 'waiting_to_return'; // Đơn hàng đang chờ trả (trong 24/48h có thể đi giao lại)
    const RETURN = 'return'; // Đơn hàng trả
    const RETURN_TRANSPORTING = 'return_transporting'; // Đơn hàng trả đang được luân chuyển
    const RETURN_SORTING = 'return_sorting'; // Đơn hàng trả đang được phân loại (tại Kho phân loại hàng)
    const RETURNING = 'returning'; // Nhân viên giao nhận đang đi trả hàng
    const RETURN_FAIL = 'return_fail'; // Trả hàng thất bại
    const RETURNED = 'returned'; // Đơn hàng đã được trả cho người bán
    const EXCEPTION = 'exception'; // Đơn hàng bị xử lý ngoại lệ (các trường hợp đi ngược quy trình)
    const DAMAGE = 'damage'; // Hàng hư hỏng
    const LOST = 'lost'; // Hàng bị mất
}