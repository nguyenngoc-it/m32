<?php

namespace Modules\NIJAVAP\Services;

class NIJAVAPOrderStatus
{
    const PENDING_PICKUP              = 'Pending Pickup'; // Đơn hàng mới tạo, đang chờ lấy
    const PICKUP_FAIL                 = 'Pickup Fail'; // Tài xế ko thể nhận hàng, chờ lấy hàng lại
    const SUCCESSFUL_PICKUP           = 'Successful Pickup'; // Tài xế nhận đơn
    const ARRIVED_AT_ORIGIN_HUB       = 'Arrived at Origin Hub'; // Đơn được giao đến trung tâm phân loại
    const ON_VEHICLE_FOR_DELIVERY     = 'On Vehicle for Delivery'; // Tài xế nhận đơn để đi giao
    const FIRST_ATTEMPT_DELIVERY_FAIL = 'First Attempt Delivery Fail'; // Tài xế giao hàng thất bại
    const RETURN_TO_SENDER_TRIGGERED  = 'Return to Sender Triggered'; // Ninjavan tiến hành gửi hàng lại cho người gửi
    const RETURNED_TO_SENDER          = 'Returned to Sender'; // Hoàn hàng cho người gửi xong
    const COMPLETED                   = 'Completed'; // Tài xế xác nhận giao hàng thành công
    const SUCCESSFUL_DELIVERY         = 'Successful Delivery'; // Tài xế xác nhận giao hàng thành công
    const CANCELLED                   = 'Cancelled'; // Huỷ đơn
}
