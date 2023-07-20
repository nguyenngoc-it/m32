<?php

namespace Modules\NIJAVAI\Services;

use Illuminate\Support\Arr;
use Modules\NIJAVAI\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class NIJAVAIService implements NIJAVAIServiceInterface
{
    /**
     * Map GHN status
     *
     * @param string $nijavaiStatus
     * @return string|null
     */
    public function mapStatus($nijavaiStatus)
    {
        return Arr::get([
            NIJAVAIOrderStatus::PICKUP => Order::STATUS_PICKED_UP,
            NIJAVAIOrderStatus::PICKUP_FAILED => Order::STATUS_READY_TO_PICK,
            NIJAVAIOrderStatus::DEPARTURE => Order::STATUS_PICKED_UP,
            NIJAVAIOrderStatus::ARRIVAL => Order::STATUS_PICKED_UP,
            NIJAVAIOrderStatus::DELIVERY => Order::STATUS_PICKED_UP,
            NIJAVAIOrderStatus::DELIVERING => Order::STATUS_DELIVERING,
            NIJAVAIOrderStatus::DELIVERED => Order::STATUS_DELIVERED,
            NIJAVAIOrderStatus::DELIVERY_FAILED => Order::STATUS_ERROR,
            NIJAVAIOrderStatus::RETURN => Order::STATUS_RETURNING,
            NIJAVAIOrderStatus::RETURNED => Order::STATUS_RETURNED,
        ], $nijavaiStatus);
    }

    /**
     * Sync delivery status from GHN order status
     *
     * @param array $input
     * @param User $user
     * @return Order
     * @throws ShippingPartnerException
     */
    public function syncOrderStatus(array $input, User $user)
    {
        return (new SyncOrderStatus($input, $user))->handle();
    }
}
