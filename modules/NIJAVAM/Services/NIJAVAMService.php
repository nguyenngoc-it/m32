<?php /** @noinspection ALL */

namespace Modules\NIJAVAM\Services;

use Illuminate\Support\Arr;
use Modules\NIJAVAM\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class NIJAVAMService implements NIJAVAMServiceInterface
{
    /**
     * Map GHN status
     *
     * @param string $NIJAVAMStatus
     * @return string|null
     */
    public function mapStatus($NIJAVAMStatus)
    {
        return Arr::get([
            NIJAVAMOrderStatus::PENDING_PICKUP => Order::STATUS_READY_TO_PICK,
            NIJAVAMOrderStatus::PICKUP_FAIL => Order::STATUS_READY_TO_PICK,
            NIJAVAMOrderStatus::SUCCESSFUL_PICKUP => Order::STATUS_PICKED_UP,
            NIJAVAMOrderStatus::ARRIVED_AT_ORIGIN_HUB => Order::STATUS_PICKED_UP,
            NIJAVAMOrderStatus::ON_VEHICLE_FOR_DELIVERY => Order::STATUS_DELIVERING,
            NIJAVAMOrderStatus::COMPLETED => Order::STATUS_DELIVERED,
            NIJAVAMOrderStatus::SUCCESSFUL_DELIVERY => Order::STATUS_DELIVERED,
            NIJAVAMOrderStatus::FIRST_ATTEMPT_DELIVERY_FAIL => Order::STATUS_ERROR,
            NIJAVAMOrderStatus::RETURN_TO_SENDER_TRIGGERED => Order::STATUS_RETURNING,
            NIJAVAMOrderStatus::RETURNED_TO_SENDER => Order::STATUS_RETURNED,
            NIJAVAMOrderStatus::CANCELLED => Order::STATUS_CANCEL,
        ], $NIJAVAMStatus);
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
