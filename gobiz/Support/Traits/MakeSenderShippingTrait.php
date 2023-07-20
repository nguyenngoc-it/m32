<?php

namespace Gobiz\Support\Traits;

use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;

trait MakeSenderShippingTrait
{
    /**
     * @param array $settingSerder
     * @return array
     */
    protected function makeSenderShippingPartner(array $settingSerder)
    {
        return $settingSerder;
    }

    /**
     * Lấy danh sách locations từ mã của các đối tác vận chuyển
     *
     * @param $provider
     * @param array $locationCodes
     * @param string $type
     * @return array
     */
    protected function getCodeLocationsFromShippingProvider($provider, array $locationCodes, $type = 'code')
    {
        return ShippingPartnerLocation::query()->where('partner_code', $provider)
            ->whereIn($type, $locationCodes)
            ->pluck('location_code', $type)->all();
    }

    /**
     * @param Order $order
     * @return string
     */
    protected function makeReferenceNumber(Order $order)
    {
        return ($order->ref) ? $order->ref : "TRACKING_".$order->id;
    }
}
