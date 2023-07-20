<?php

namespace Modules\Order\Jobs;

use App\Base\Job;
use Gobiz\Workflow\WorkflowException;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Psr\Log\LoggerInterface;
use Gobiz\Log\LogService;

class SyncOrderStatusJob extends Job
{
    public $queue = 'sync_order_status';

    /**
     * @var int
     */
    protected $orderId;


    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SyncOrderStatusJob constructor.
     * @param $orderId
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @throws WorkflowException
     */
    public function handle()
    {
        $this->logger = LogService::logger('sync_order_status');

        $order = Order::find($this->orderId);
        if (!$order instanceof Order) {
            $this->logger->info('order invalid:' . $this->orderId);
        }

        // call api to API shipping Partner
        $shippingPartnerOrder = Service::appLog()->logTimeExecute(function () use ($order) {
            return $order->shippingPartner->partner()->getOrderInfo($order);
        }, LogService::logger('sync_order_status_time'),
            ' order: ' . $order->id
            . ' - code: ' . $order->code
            . ' - tracking_no: ' . $order->tracking_no
            . ' - ref: ' . $order->ref
            . ' - shippingPartner: ' . $order->shippingPartner->code
        );

        if (!$shippingPartnerOrder instanceof ShippingPartnerOrder) {
            $this->logger->info('ShippingPartnerOrder invalid:' . $order->tracking_no);
        }

        if (empty($shippingPartnerOrder->status)) {
            $this->logger->info('ShippingPartnerOrder status invalid:' . $order->tracking_no);
            return;
        }

        if ($order->status != $shippingPartnerOrder->status) {
            $user = Service::user()->getSystemUser();
            if ($order->canChangeStatus($shippingPartnerOrder->status)) {
                $order->changeStatus($shippingPartnerOrder->status, $user);
            } else {
                $this->logger->error('ShippingPartnerOrder cant change Status from ' . $order->status . ' to ' . $shippingPartnerOrder->status);
            }
        }
    }
}
