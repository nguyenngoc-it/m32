<?php

namespace Modules\GHN\Services;

use Illuminate\Support\Arr;
use Modules\GHN\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class GHNService implements GHNServiceInterface
{
    /**
     * Map GHN status
     *
     * @param string $ghnStatus
     * @return string|null
     */
    public function mapStatus($ghnStatus)
    {
        return Arr::get([
            GHNOrderStatus::READY_TO_PICK => Order::STATUS_READY_TO_PICK,
            GHNOrderStatus::CANCEL => Order::STATUS_CANCEL,
            GHNOrderStatus::PICKING => Order::STATUS_READY_TO_PICK,
            GHNOrderStatus::MONEY_COLLECT_PICKING => Order::STATUS_READY_TO_PICK,
            GHNOrderStatus::PICKED => Order::STATUS_PICKED_UP,
            GHNOrderStatus::STORING => Order::STATUS_DELIVERING,
            GHNOrderStatus::TRANSPORTING => Order::STATUS_DELIVERING,
            GHNOrderStatus::SORTING => Order::STATUS_DELIVERING,
            GHNOrderStatus::DELIVERING => Order::STATUS_DELIVERING,
            GHNOrderStatus::MONEY_COLLECT_DELIVERING => Order::STATUS_DELIVERING,
            GHNOrderStatus::DELIVERED => Order::STATUS_DELIVERED,
            GHNOrderStatus::DELIVERY_FAIL => Order::STATUS_RETURNING,
            GHNOrderStatus::WAITING_TO_RETURN => Order::STATUS_RETURNING,
            GHNOrderStatus::RETURN => Order::STATUS_RETURNING,
            GHNOrderStatus::RETURN_TRANSPORTING => Order::STATUS_RETURNING,
            GHNOrderStatus::RETURN_SORTING => Order::STATUS_RETURNING,
            GHNOrderStatus::RETURNING => Order::STATUS_RETURNING,
            GHNOrderStatus::RETURN_FAIL => Order::STATUS_RETURNING,
            GHNOrderStatus::RETURNED => Order::STATUS_RETURNED,
            GHNOrderStatus::EXCEPTION => Order::STATUS_ERROR,
            GHNOrderStatus::DAMAGE => Order::STATUS_ERROR,
            GHNOrderStatus::LOST => Order::STATUS_ERROR,
        ], $ghnStatus);
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