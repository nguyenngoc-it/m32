<?php

namespace Modules\JNTM\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\Helper;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Database\Eloquent\Builder;
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
        $this->logger = LogService::logger('flash_webhook', [
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
        $orderCode    = Arr::get($this->input, 'outTradeNo');
        $trackingCode = Arr::get($this->input, 'pno');
        $status       = Arr::get($this->input, 'state');

        // dd($orderCode, $trackingCode, $status);

        if(empty($orderCode) && empty($trackingCode)) {
            $this->logger->error('WEBHOOK.EMPTY_DATA');
            return null;
        }

        if (!$order = $this->findOrder($orderCode, $trackingCode)) {
            $this->logger->error('WEBHOOK.ORDER_NOT_FOUND');
            return null;
        }

        $order->update(['original_status' => $status]);

        if (!$orderStatus = Service::flash()->mapStatus($status)) {
            $this->logger->error('WEBHOOK.CANT_MAP_STATUS');
            throw new ShippingPartnerException("FLASH.WEBHOOK.CANT_MAP_STATUS: " . json_encode([
                    'order'          => $orderCode,
                    'waybill_number' => $trackingCode,
                    'flash_status'   => $status,
                ]));
        }

        if ($order->status == $orderStatus) {
            return $order;
        }

        $errorPayload = [
            'order'       => $orderCode,
            'status'      => $order->status,
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
            throw new ShippingPartnerException("FLASH.WEBHOOK.CHANGE_STATUS_FAILED: " . json_encode($errorPayload));
        }
    }

    /**
     * @param string $code
     * @param string $trackingCode
     * @return Order|object|null
     */
    protected function findOrder($code, $trackingCode)
    {
        $query = Order::query()->where('shipping_partner_code', ShippingPartner::PARTNER_FLASH);
        if(!empty($code)) {
            $query->where('code', trim($code));
        } else {
            $query->where('tracking_no', trim($trackingCode));
        }

        return $query->first();
    }
}
