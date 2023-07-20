<?php

namespace Modules\JNEI\Commands;

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
        $this->input  = $input;
        $this->user   = $user;
        $this->logger = LogService::logger('jnei', [
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
        $referenceNo         = Arr::get($this->input, 'reference_no');
        $trackingReferenceNo = Arr::get($this->input, 'tracking_reference_no');
        $status              = Arr::get($this->input, 'rowstate_name');
        if (!$order = $this->findOrder($referenceNo, $trackingReferenceNo)) {
            $this->logger->error('WEBHOOK.ORDER_NOT_FOUND');
            return null;
//            throw new ShippingPartnerException("JNEI.WEBHOOK.ORDER_NOT_FOUND: {$referenceNo}");
        }

        $order->update(['original_status' => $status]);

        if (!$orderStatus = Service::jnei()->mapStatus($status)) {
            $this->logger->error('WEBHOOK.CANT_MAP_STATUS');
            throw new ShippingPartnerException("JNEI.WEBHOOK.CANT_MAP_STATUS: " . json_encode([
                    'reference_no' => $referenceNo,
                    'tracking_reference_no' => $trackingReferenceNo,
                    'jnei_status' => $status,
                ]));
        }

        if ($order->status == $orderStatus) {
            return $order;
        }

        $errorPayload = [
            'order' => $referenceNo,
            'status' => $order->status,
            'next_status' => $orderStatus,
        ];

        if (!$order->canChangeStatus($orderStatus)) {
            $this->logger->error('WEBHOOK.CANT_CHANGE_STATUS', $errorPayload);
            return null;
//            throw new ShippingPartnerException("JNEI.WEBHOOK.CANT_CHANGE_STATUS: " . json_encode($errorPayload));
        }

        try {
            $order->changeStatus($orderStatus, $this->user);

            return $order;
        } catch (WorkflowException $e) {
            $this->logger->error('WEBHOOK.CHANGE_STATUS_FAILED', $errorPayload);
            throw new ShippingPartnerException("JNEI.WEBHOOK.CHANGE_STATUS_FAILED: " . json_encode($errorPayload));
        }
    }

    /**
     * @param string $code
     * @param null $trackingCode
     * @return Order|object|null
     */
    protected function findOrder($code, $trackingCode)
    {
        return Order::query()->where('shipping_partner_code', ShippingPartner::PARTNER_JNEI)
            ->where(function (Builder $builder) use ($code, $trackingCode) {
                $builder->where('code', $code)->orWhere('tracking_no', $trackingCode);
            })
            ->first();
    }
}
