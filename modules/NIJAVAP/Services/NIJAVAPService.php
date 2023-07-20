<?php /** @noinspection ALL */

namespace Modules\NIJAVAP\Services;

use Illuminate\Support\Arr;
use Modules\NIJAVAP\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class NIJAVAPService implements NIJAVAPServiceInterface
{
    /**
     * Map GHN status
     *
     * @param string $NIJAVAMStatus
     * @return string|null
     */
    public function mapStatus($NIJAVAPStatus)
    {
        return Arr::get([
            NIJAVAPOrderStatus::PENDING_PICKUP => Order::STATUS_READY_TO_PICK,
            NIJAVAPOrderStatus::PICKUP_FAIL => Order::STATUS_READY_TO_PICK,
            NIJAVAPOrderStatus::SUCCESSFUL_PICKUP => Order::STATUS_PICKED_UP,
            NIJAVAPOrderStatus::ARRIVED_AT_ORIGIN_HUB => Order::STATUS_PICKED_UP,
            NIJAVAPOrderStatus::ON_VEHICLE_FOR_DELIVERY => Order::STATUS_DELIVERING,
            NIJAVAPOrderStatus::COMPLETED => Order::STATUS_DELIVERED,
            NIJAVAPOrderStatus::SUCCESSFUL_DELIVERY => Order::STATUS_DELIVERED,
            NIJAVAPOrderStatus::FIRST_ATTEMPT_DELIVERY_FAIL => Order::STATUS_ERROR,
            NIJAVAPOrderStatus::RETURN_TO_SENDER_TRIGGERED => Order::STATUS_RETURNING,
            NIJAVAPOrderStatus::RETURNED_TO_SENDER => Order::STATUS_RETURNED,
            NIJAVAPOrderStatus::CANCELLED => Order::STATUS_CANCEL,
        ], $NIJAVAPStatus);
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
