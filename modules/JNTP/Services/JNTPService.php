<?php

namespace Modules\JNTP\Services;

use Illuminate\Support\Arr;
use Modules\JNTP\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class JNTPService implements JNTPServiceInterface
{
    /**
     * Map GHN status
     *
     * @param string $jntpStatus
     * @return string|null
     */
    public function mapStatus($jntpStatus)
    {
        return Arr::get([
            JNTPOrderStatus::PICKUP_FAILED => Order::STATUS_READY_TO_PICK,
            JNTPOrderStatus::ARRIVAL => Order::STATUS_DELIVERING,
            JNTPOrderStatus::PICKUP => Order::STATUS_DELIVERING,
            JNTPOrderStatus::DEPARTURE => Order::STATUS_DELIVERING,
            JNTPOrderStatus::ON_DELIVERY => Order::STATUS_DELIVERING,
            JNTPOrderStatus::DELIVERED => Order::STATUS_DELIVERED,
            JNTPOrderStatus::RETURNED => Order::STATUS_RETURNED,
        ], $jntpStatus);
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

    /**
     * Get order remark
     *
     * @param Order $order
     * @return string
     */
    public function getRemark(Order $order)
    {
        $contentRemark = '';
        $items         = $order->items->map(function (OrderItem $orderItem) use (&$contentRemark) {
            $contentRemark .= $orderItem->quantity . ' ' . $orderItem->name . ' / ';
            return [
                'itemname' => $orderItem->name,
                'number' => $orderItem->quantity,
                'itemvalue' => $orderItem->price,
            ];
        });
        if ($contentRemark) {
            $contentRemark = substr($contentRemark, 0, -3);
        }
        $countQuantityItems = $items->sum('number');
        $totalQuantity      = $countQuantityItems ?: 1;

        return $totalQuantity . ' PCS-FIN-' . $contentRemark . '-' . $order->cod;
    }
}
