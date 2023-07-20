<?php

namespace Modules\JNTVN\Services;

use Illuminate\Support\Arr;
use Modules\JNTVN\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class JNTVNService implements JNTVNServiceInterface
{
    /**
     * Map GHN status
     *
     * @param string $jntvnStatus
     * @return string|null
     */
    public function mapStatus($jntvnStatus)
    {
        return Arr::get([
            JNTVNOrderStatus::PICK_UP => Order::STATUS_READY_TO_PICK,
            JNTVNOrderStatus::DEPARTURE => Order::STATUS_DELIVERING,
            JNTVNOrderStatus::ARRIVAL => Order::STATUS_DELIVERING,
            JNTVNOrderStatus::DELIVERY => Order::STATUS_DELIVERING,
            JNTVNOrderStatus::P_O_D => Order::STATUS_PICKED_UP,
            JNTVNOrderStatus::RETURN => Order::STATUS_RETURNING,
            JNTVNOrderStatus::R_P_O_D => Order::STATUS_RETURNED
        ], $jntvnStatus);
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
