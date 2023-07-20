<?php

namespace Modules\JNTC\Jobs;

use App\Base\Job;
use Modules\JNTC\Services\JNTCShippingPartner;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartner;

class SyncQueryTrackingJob extends Job
{
    public $queue = 'sync_query_tracking';

    /**
     * @var ShippingPartner
     */
    protected $shippingPartner;

    /**
     * @var array
     */
    protected $trackingNo;

    /**
     * @var
     */
    protected $orderId;

    /**
     * SyncQueryTrackingJob constructor.
     * @param $orderId
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle()
    {
        $order = Order::find($this->orderId);
        $jntc   = new JNTCShippingPartner($order->shippingPartner->settings, config('services.jntc.api_url'));

        $tracking = $jntc->getTrackings([$order->tracking_no]);
        if($tracking && $order->status != $tracking->status) {
            $order->original_status = $tracking->originStatus;
            $order->save();
            $order->changeStatusWithoutFlow($tracking->status);
        }
    }
}
