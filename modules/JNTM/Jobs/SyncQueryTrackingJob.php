<?php

namespace Modules\JNTM\Jobs;

use App\Base\Job;
use Gobiz\Workflow\WorkflowException;
use Modules\JNTM\Services\JNTMShippingPartner;
use Modules\Order\Models\Order;
use Modules\Service;
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
     * @throws WorkflowException
     */
    public function handle()
    {
        $orders    = $this->shippingPartner->orders()
            ->whereIn('tracking_no', $this->trackingNos)->get();
        $jntm      = new JNTMShippingPartner($this->shippingPartner->settings, config('services.jntm.api_url'));
        $trackings = $jntm->getTrackings($this->trackingNos);
        /** @var Tracking $tracking */
        foreach ($trackings as $tracking) {
            /** @var Order $trackingOrder */
            $trackingOrder = $orders->where('tracking_no', $tracking->trackingCode)->first();
            if ($trackingOrder && $trackingOrder->canChangeStatus($tracking->status)) {
                $trackingOrder->original_status = $tracking->originStatus;
                $trackingOrder->save();
                $trackingOrder->changeStatus($tracking->status, Service::user()->getSystemUser());
            }
        }
    }
}
