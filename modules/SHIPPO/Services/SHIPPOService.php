<?php

namespace Modules\SHIPPO\Services;

use Illuminate\Support\Arr;
use Modules\SHIPPO\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class SHIPPOService implements SHIPPOServiceInterface
{
    /**
     * Map GHN status
     *
     * @param string $shippoStatus
     * @return string|null
     */
    public function mapStatus($shippoStatus)
    {
        return Arr::get([
            SHIPPOOrderStatus::PENDING => Order::STATUS_CREATING,
            SHIPPOOrderStatus::ACCEPTED => Order::STATUS_READY_TO_PICK,
            SHIPPOOrderStatus::MERCHANT_DELIVERING => Order::STATUS_READY_TO_PICK,
            SHIPPOOrderStatus::PUTAWAY => Order::STATUS_PICKED_UP,
            SHIPPOOrderStatus::TRANSPORTING => Order::STATUS_DELIVERING,
            SHIPPOOrderStatus::READY_FOR_DELIVERY => Order::STATUS_DELIVERING,
            SHIPPOOrderStatus::DELIVERING => Order::STATUS_DELIVERING,
            SHIPPOOrderStatus::DELIVERED => Order::STATUS_DELIVERED,
            SHIPPOOrderStatus::CANCELLED => Order::STATUS_CANCEL,
            SHIPPOOrderStatus::MIA => Order::STATUS_ERROR,
            SHIPPOOrderStatus::DELIVERY_CANCELLED => Order::STATUS_CANCEL,
        ], $shippoStatus);
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
