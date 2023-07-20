<?php

namespace Modules\SNAPPY\Services;

class SNAPPYOrderStatus
{
    const REQUEST_RECEIVED = 'request_received'; // Đơn mới
    const PROCESSING_PICKED_UP = 'processing_picked_up'; // Đang lấy hàng
    const PICKED_UP_FAIL = 'picked_up_fail'; // Chưa lấy được hàng
    const PICKED_UP = 'picked_up'; // Đã lấy
    const WAITING_ON_THE_WAY = 'waiting_on_the_way'; // Chờ trung chuyển
    const PROCESSING_ON_THE_WAY = 'processing_on_the_way'; // Đang trung chuyển
    const IMPORT_PICKING_WAREHOUSE = 'import_picking_warehouse'; // 	Nhập kho lấy


    const ON_THE_WAY = 'on_the_way'; // Đang trong kho
    const OUT_FOR_DELIVERY = 'out_for_delivery'; // Đang giao
    const PART_DELIVERY = 'part_delivery'; // Giao một phần
    const DELIVERED = 'delivered'; // Giao thành công
    const UNDELIVERABLE = 'undeliverable'; // Giao không thành
    const WAITING_FOR_RETURN = 'waiting_for_return'; // Chờ hoàn
    const IMPORT_RETURNING_WAREHOUSE = 'import_returning_warehouse'; // Nhập kho hoàn
    const ON_THE_WAY_RETURNING = 'on_the_way_returning'; // Trung chuyển hoàn
    const RETURNING = 'returning'; // Đang hoàn
    const RETURNED = 'returned'; // Đã hoàn
    const CANCELED = 'canceled'; // Đã hủy
}

