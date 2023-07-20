<?php

namespace Modules\ShippingPartner\Services;

use Illuminate\Support\Arr;
use Modules\Location\Models\Location;
use Modules\Service;

abstract class AbstractShippingPartner implements ShippingPartnerInterface
{
    /**
     * @inheritDoc
     */
    public function shippingFee(Location $senderWard, Location $receiveWard, ShippingPartnerSize $shippingPartnerSize)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getOrderStampsUrl(array $orders)
    {
        return $this instanceof OrderStampRenderable ? $this->makeOrderStampsUrl(Arr::pluck($orders, 'id')) : null;
    }

    /**
     * Tạo url in tem trên m28 cho danh sách đơn
     *
     * @param array $orderIds
     * @return string
     */
    protected function makeOrderStampsUrl(array $orderIds)
    {
        $orderIds = implode(',', $orderIds);
        $token = Service::app()->tokenGenerator()->make($orderIds, config('services.order.stamp_token_expire'));

        return url('order-stamps').'?'.http_build_query(['token' => $token]);
    }

    /**
     * @param array $urls
     * @return string
     * @throws \Gobiz\Support\RestApiException
     */
    protected function mergePdfStamps(array $urls)
    {
        if (count($urls) === 1) {
            return Arr::first($urls);
        }

        $filename = Service::app()->utilsApi()->mergeFiles($urls)->getData('filename');

        return Service::app()->utilsApi()->urlMergeFile($filename, config('services.order.stamp_token_expire'));
    }
}
