<?php

namespace Modules\ShippingPartner\Services;

class ShippingPartnerOrder
{
    /**
     * Mã đơn
     *
     * @var string
     */
    public $code;

    /**
     * Mã vận đơn
     *
     * @var string
     */
    public $trackingNo;

    /**
     * Tổng phí
     *
     * @var float
     */
    public $fee;

    /**
     * Trạng thái đơn
     *
     * @var string
     */
    public $status;

    /**
     * Thông tin người gửi hàng
     * [
     *  'name','phone','province','district','ward','address'
     * ]
     *
     * @var
     */
    public $sender;

    /**
     * @var array
     */
    public $query;

    /**
     * @var array
     */
    public $response;

    public $sortingNo;

    public $sortingCode;
}
