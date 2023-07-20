<?php

namespace Modules\SAPI\Services;

class SAPIOrderStatus
{
    const PICKUP            = 'ENTRI (SEDANG DI PICKUP)'; // Đơn hàng mới tạo, đang chờ lấy
    const VERIFIED          = 'ENTRI VERIFIED'; // Đơn đã được xác nhận
    const MANIFEST_OUTGOING = 'MANIFEST OUTGOING'; // Nhập kho đóng hàng
    const OUTGOING_SMU      = 'OUTGOING SMU'; // Đóng hàng xong
    const INCOMING          = 'INCOMING'; // Sẵn sàng giao
    const DELIVERY          = 'DELIVERY'; // Đang giao hàng
    const DELIVERED         = 'POD - DELIVERED'; // Nhân viên giao nhận đã giao hàng
    const UNDELIVERED       = 'POD - UNDELIVERED'; // Giao hàng chưa thành công
    const ANTAR_ULANG       = 'POD - ANTAR ULANG'; // Đang trả lại hàng
    const RETURN            = 'DELIVERY RETURN'; // Chi nhánh đã nhận lại hàng
    const RETURN_TO_CLIENT  = 'SHIPMENT RETURN TO CLIENT'; // Hàng đã trả lại cho người bán
}
