<?php

namespace Modules\SHIPPO\Commands;

use Gobiz\Log\LogService;
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
        $this->logger = LogService::logger('shippo_webhook', [
            'context' => ['order' => $this->input],
        ]);
    }

    /**
     * @return Order
     * @throws ShippingPartnerException
     */
    public function handle()
    {
        $orderCode = Arr::get($this->input, 'code');
        $status    = Arr::get($this->input, 'status');

        $this->logger->info('WEBHOOK.RECEIVED');
        if (!$order = $this->findOrder($orderCode)) {
            $this->logger->error('WEBHOOK.ORDER_NOT_FOUND');
            return null;
            //throw new ShippingPartnerException("SHIPPO.WEBHOOK.ORDER_NOT_FOUND: {$orderCode}");
        }

        $order->update(['original_status' => $status]);

        if (!$orderStatus = Service::shippo()->mapStatus($status)) {
            $this->logger->error('WEBHOOK.CANT_MAP_STATUS');
            throw new ShippingPartnerException("SHIPPO.WEBHOOK.CANT_MAP_STATUS: " . json_encode([
                    'order' => $orderCode,
                    'shippo_status' => $status,
                ]));
        }

        if ($order->status === $orderStatus) {
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
//            throw new ShippingPartnerException("SHIPPO.WEBHOOK.CANT_CHANGE_STATUS: " . json_encode($errorPayload));
        }

        try {
            $order->changeStatus($orderStatus, $this->user);

            return $order;
        } catch (WorkflowException $e) {
            $this->logger->error('WEBHOOK.CHANGE_STATUS_FAILED', $errorPayload);
            throw new ShippingPartnerException("SHIPPO.WEBHOOK.CHANGE_STATUS_FAILED: " . json_encode($errorPayload));
        }
    }

    /**
     * @param string $code
     * @return Order|object|null
     */
    protected function findOrder($code)
    {
        return Order::query()->where([
            'shipping_partner_code' => ShippingPartner::PARTNER_SHIPPO,
            'code' => $code,
        ])->first();
    }
}
