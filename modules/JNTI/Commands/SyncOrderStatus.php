<?php

namespace Modules\JNTI\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\Helper;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Support\Arr;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncOrderStatus
{
    /**
     * @var array
     */
    protected $input = [];

    /**
     * @var User
     */
    protected $user;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SyncOrderStatus constructor
     *
     * @param array $input
     * @param User $user
     */
    public function __construct(array $input, User $user)
    {
        $this->input  = $input;
        $this->user   = $user;
        $this->logger = LogService::logger('jnti_webhook', [
            'context' => ['order' => $this->input],
        ]);
    }

    /**
     * @return Order
     * @throws ShippingPartnerException
     */
    public function handle()
    {
        $this->logger->info('WEBHOOK.RECEIVED');
        $orderCode    = Arr::get($this->input, 'order_number');
        $trackingCode = Arr::get($this->input, 'waybill_number');
        $status       = Arr::get($this->input, 'status');
        $status       = str_replace(' ', '_', $status); //<0xa0> đậu xanh
        $status       = Helper::clean($status);

        if(empty($orderCode) && empty($trackingCode)) {
            $this->logger->error('WEBHOOK.EMPTY_DATA');
            return null;
        }

        if (!$order = $this->findOrder($orderCode, $trackingCode)) {
            $this->logger->error('WEBHOOK.ORDER_NOT_FOUND');
            return null;
        }

        $order->update(['original_status' => $status]);

        if (!$orderStatus = Service::jnti()->mapStatus($status)) {
            $this->logger->error('WEBHOOK.CANT_MAP_STATUS');
            throw new ShippingPartnerException("JNTI.WEBHOOK.CANT_MAP_STATUS: " . json_encode([
                    'order' => $orderCode,
                    'waybill_number' => $trackingCode,
                    'jnti_status' => $status,
                ]));
        }

        if ($order->status == $orderStatus) {
            return $order;
        }

        $errorPayload = [
            'order' => $orderCode,
            'status' => $order->status,
            'next_status' => $orderStatus,
        ];

        if (!$order->canChangeStatus($orderStatus)) {
            $this->logger->error('WEBHOOK.CANT_CHANGE_STATUS', $errorPayload);
            return null;
        }

        try {
            $order->changeStatus($orderStatus, $this->user);

            return $order;
        } catch (WorkflowException $e) {
            $this->logger->error('WEBHOOK.CHANGE_STATUS_FAILED', $errorPayload);
            throw new ShippingPartnerException("JNTI.WEBHOOK.CHANGE_STATUS_FAILED: " . json_encode($errorPayload));
        }
    }

    /**
     * @param string $code
     * @param null $trackingCode
     * @return Order|object|null
     */
    protected function findOrder($code, $trackingCode)
    {
        $query = Order::query()->where('shipping_partner_code', ShippingPartner::PARTNER_JNTI);
        if(!empty($code)) {
             $query->where('code', trim($code));
        } else {
            $query->where('tracking_no', trim($trackingCode));
        }

        return $query->first();
    }
}
