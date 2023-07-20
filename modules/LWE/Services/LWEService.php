<?php

namespace Modules\LWE\Services;

use Illuminate\Support\Arr;
use Modules\LWE\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class LWEService implements LWEServiceInterface
{
    /**
     * Map LWE status
     *
     * @param string $lweStatus
     * @return string|null
     */
    public function mapStatus($lweStatus)
    {
        return Arr::get([
            LWEOrderStatus::READY_FOR_PICKUP => Order::STATUS_READY_TO_PICK,
            LWEOrderStatus::RECEIVED_AT_DESTINATION => Order::STATUS_PICKED_UP,
            LWEOrderStatus::OUT_FOR_DELIVERY => Order::STATUS_DELIVERING,
            LWEOrderStatus::DELIVERED => Order::STATUS_DELIVERED,
            LWEOrderStatus::RETURNING_39 => Order::STATUS_RETURNING,
            LWEOrderStatus::RETURNING_42 => Order::STATUS_RETURNING,
            LWEOrderStatus::CANCELED => Order::STATUS_CANCEL,
            LWEOrderStatus::ERROR_14 => Order::STATUS_ERROR,
            LWEOrderStatus::ERROR_20 => Order::STATUS_ERROR,
            LWEOrderStatus::ERROR_22 => Order::STATUS_ERROR,
            LWEOrderStatus::ERROR_23 => Order::STATUS_ERROR,
            LWEOrderStatus::ERROR_26 => Order::STATUS_ERROR,
            LWEOrderStatus::ERROR_27 => Order::STATUS_ERROR,
            LWEOrderStatus::ERROR_28 => Order::STATUS_ERROR,
            LWEOrderStatus::ERROR_29 => Order::STATUS_ERROR,
            LWEOrderStatus::ERROR_30 => Order::STATUS_ERROR,
            LWEOrderStatus::ERROR_36 => Order::STATUS_ERROR,
        ], $lweStatus);
    }

    /**
     * Sync delivery status from LWE order status
     *
     * @param array $input
     * @param User $user
     * @return void
     * @throws ShippingPartnerException
     */
    public function syncOrderStatus(array $input, User $user)
    {
        return (new SyncOrderStatus($input, $user))->handle();
    }
}
