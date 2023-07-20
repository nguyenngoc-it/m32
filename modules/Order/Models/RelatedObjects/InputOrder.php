<?php

namespace Modules\Order\Models\RelatedObjects;

use Illuminate\Support\Arr;
use Modules\ShippingPartner\Models\ShippingPartner;

/**
 * Class InputOrder
 * @package Modules\Order\Models\RelatedObjects
 *
 */
class InputOrder extends RelatedObjectBase
{
    public $ref;
    public $shippingCarrierCode;
    public $shippingConnectCode;
    public $receiverName;
    public $receiverPhone;
    public $receiverAddress;
    public $receiverDistrictCode;
    public $receiverWardCode;
    public $weight;
    public $cod;
    public $items;
    public $trackingNo;

    /** @var ShippingPartner $shippingPartner */
    protected $shippingPartner;

    /**
     * InputOrder constructor.
     * @param array $inputs
     */
    public function __construct(array $inputs)
    {
        $this->ref                  = Arr::get($inputs, 'ref');
        $this->shippingCarrierCode  = Arr::get($inputs, 'shipping_carrier_code');
        $this->shippingConnectCode  = Arr::get($inputs, 'shipping_connect_code');
        $this->receiverName         = Arr::get($inputs, 'receiver_name');
        $this->receiverPhone        = Arr::get($inputs, 'receiver_phone');
        $this->receiverAddress      = Arr::get($inputs, 'receiver_address');
        $this->receiverDistrictCode = Arr::get($inputs, 'receiver_district_code');
        $this->receiverWardCode     = Arr::get($inputs, 'receiver_ward_code');
        $this->weight               = Arr::get($inputs, 'weight');
        $this->cod                  = Arr::get($inputs, 'cod');
        $this->items                = Arr::get($inputs, 'items');
        $this->trackingNo           = Arr::get($inputs, 'tracking_no');
    }

    /**
     * @return ShippingPartner
     */
    public function getShippingPartner(): ShippingPartner
    {
        return $this->shippingPartner;
    }

    /**
     * @param ShippingPartner $shippingPartner
     */
    public function setShippingPartner(ShippingPartner $shippingPartner): void
    {
        $this->shippingPartner = $shippingPartner;
    }
}
