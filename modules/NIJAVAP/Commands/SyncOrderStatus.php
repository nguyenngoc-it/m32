<?php

namespace Modules\NIJAVAP\Commands;

use Gobiz\Log\LogService;
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
        $this->input = $input;
        $this->user  = $user;
    }

    /**
     * @return Order
     * @throws ShippingPartnerException
     */
    public function handle()
    {
        $this->logger = LogService::logger('nijavap-hook');
        $orderCode    = Arr::get($this->input, 'tracking_ref_no');
        $trackingCode = Arr::get($this->input, 'tracking_id');
        $status       = Arr::get($this->input, 'status');
        if (!$order = $this->findOrder($orderCode, $trackingCode)) {
            $this->logger->error('WEBHOOK.ORDER_NOT_FOUND');
            return null;
        }

        $order->update(['original_status' => $status]);

        if (!$orderStatus = Service::NIJAVAP()->mapStatus($status)) {
            $this->logger->error('WEBHOOK.CANT_MAP_STATUS');
            return null;
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
            throw new ShippingPartnerException("NIJAVAP.WEBHOOK.CHANGE_STATUS_FAILED: " . json_encode($errorPayload));
        }
    }

    /**
     * @param string $code
     * @param null $trackingCode
     * @return Order|object|null
     */
    protected function findOrder($code, $trackingCode)
    {
        return Order::query()->where('shipping_partner_code', ShippingPartner::PARTNER_NIJAVAP)
            ->where(function (Builder $builder) use ($code, $trackingCode) {
                $builder->where('code', $code)->orWhere('tracking_no', $trackingCode);
            })
            ->first();
    }
}
