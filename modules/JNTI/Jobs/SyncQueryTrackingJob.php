<?php

namespace Modules\JNTI\Jobs;

use App\Base\Job;
use Gobiz\Workflow\WorkflowException;
use GuzzleHttp\Exception\GuzzleException;
use Modules\JNTI\Services\JNTIShippingPartner;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
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
     * @throws WorkflowException
     * @throws GuzzleException
     */
    public function handle()
    {
        $orders = $this->shippingPartner->orders()
            ->whereIn('tracking_no', $this->trackingNos)->get();
        $jnti   = new JNTIShippingPartner($this->shippingPartner->settings, config('services.jnti.api_url'));
        foreach ($this->trackingNos as $trackingNo) {
            $trackings = $jnti->getTrackings([$trackingNo]);
            /** @var Tracking $tracking */
            foreach ($trackings as $tracking) {
                /** @var Order $trackingOrder */
                $trackingOrder = $orders->where('tracking_no', $tracking->trackingCode)->first();
                if (($trackingOrder->status != $tracking->status) && $trackingOrder->canChangeStatus($tracking->status)) {
                    $trackingOrder->original_status = $tracking->originStatus;
                    $trackingOrder->save();
                    $trackingOrder->changeStatus($tracking->status, Service::user()->getSystemUser());
                }
            }
        }
    }
}
