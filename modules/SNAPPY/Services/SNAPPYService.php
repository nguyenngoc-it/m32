<?php

namespace Modules\SNAPPY\Services;

use Illuminate\Support\Arr;
use Modules\SNAPPY\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class SNAPPYService implements SNAPPYServiceInterface
{
    /**
     * Map SNAPPY status
     *
     * @param string $snappyStatus
     * @return string|null
     */
    public function mapStatus(string $snappyStatus): ?string
    {
        return Arr::get([
            SNAPPYOrderStatus::REQUEST_RECEIVED => Order::STATUS_READY_TO_PICK,
            SNAPPYOrderStatus::PROCESSING_PICKED_UP => Order::STATUS_READY_TO_PICK,
            SNAPPYOrderStatus::PICKED_UP_FAIL => Order::STATUS_READY_TO_PICK,
            SNAPPYOrderStatus::PICKED_UP => Order::STATUS_PICKED_UP,
            SNAPPYOrderStatus::WAITING_ON_THE_WAY => Order::STATUS_DELIVERING,
            SNAPPYOrderStatus::PROCESSING_ON_THE_WAY => Order::STATUS_DELIVERING,
            SNAPPYOrderStatus::ON_THE_WAY => Order::STATUS_DELIVERING,
            SNAPPYOrderStatus::OUT_FOR_DELIVERY => Order::STATUS_DELIVERING,
            SNAPPYOrderStatus::PART_DELIVERY => Order::STATUS_DELIVERING,
            SNAPPYOrderStatus::IMPORT_PICKING_WAREHOUSE => Order::STATUS_DELIVERING,
            SNAPPYOrderStatus::DELIVERED => Order::STATUS_DELIVERED,
            SNAPPYOrderStatus::UNDELIVERABLE => Order::STATUS_ERROR,
            SNAPPYOrderStatus::WAITING_FOR_RETURN => Order::STATUS_RETURNING,
            SNAPPYOrderStatus::IMPORT_RETURNING_WAREHOUSE => Order::STATUS_RETURNING,
            SNAPPYOrderStatus::ON_THE_WAY_RETURNING => Order::STATUS_RETURNING,
            SNAPPYOrderStatus::RETURNING => Order::STATUS_RETURNING,
            SNAPPYOrderStatus::RETURNED => Order::STATUS_RETURNED,
            SNAPPYOrderStatus::CANCELED => Order::STATUS_CANCEL,
        ], $snappyStatus);
    }

    /**
     * Sync delivery status from SNAPPY order status
     *
     * @param array $input
     * @param User $user
     * @return Order
     * @throws ShippingPartnerException
     */
    public function syncOrderStatus(array $input, User $user): Order
    {
        return (new SyncOrderStatus($input, $user))->handle();
    }
}
