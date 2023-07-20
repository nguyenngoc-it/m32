<?php

namespace Modules\JNTI\Services;

class JNTIOrderStatus
{
    const ORDER_CREATED   = '101'; // Đơn hàng mới tạo, đang chờ lấy
    const ERROR_PICKUP    = '151'; // Lấy hàng không thành công
    const DELIVERING      = '100';
    const DELIVERY_FAILED = '152'; // Giao hàng không thành công
    const DELIVERED       = '200';
    const ERROR_GOOD      = '150'; // Vấn đề với lô hàng, tạm giữ
    const RETURN          = '401'; // Đang trả hàng
    const RETURNED        = '402'; // Đã trả hàng
    const CANNCED_BY_API  = '162'; // HUỷ qua api
    const CANNCED_BY_JNT  = '163'; // HUỷ qua dvvc
}
