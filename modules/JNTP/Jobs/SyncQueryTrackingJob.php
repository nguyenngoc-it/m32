<?php

namespace Modules\JNTP\Jobs;

use App\Base\Job;
use Modules\JNTP\Services\JNTPShippingPartner;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerException;

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
    protected $trackingNos;

    /**
     * SyncOrderStatusJob constructor
     *
     * @param ShippingPartner $shippingPartner
     * @param array $trackingNos
     */
    public function __construct(ShippingPartner $shippingPartner, array $trackingNos)
    {
        $this->shippingPartner = $shippingPartner;
        $this->trackingNos     = $trackingNos;
    }

    /**
     * @throws ShippingPartnerException
     */
    public function handle()
    {
        $orders = $this->shippingPartner->orders()
            ->whereIn('tracking_no', $this->trackingNos)->get();
        $jntp   = new JNTPShippingPartner($this->shippingPartner->settings, config('services.jntp.api_url'));

        foreach ($this->trackingNos as $trackingNo) {
            $trackings = $jntp->getTrackings([$trackingNo]);
            /** @var Tracking $tracking */
            foreach ($trackings as $tracking) {
                /** @var Order $trackingOrder */
                $trackingOrder = $orders->where('tracking_no', $tracking->trackingCode)->first();
                if ($trackingOrder && ($trackingOrder->status != $tracking->status)) {
                    $trackingOrder->original_status = $tracking->originStatus;
                    $trackingOrder->save();
                    $trackingOrder->changeStatusWithoutFlow($tracking->status);
                }
            }
        }
    }
}
