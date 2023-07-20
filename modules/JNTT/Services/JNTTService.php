<?php

namespace Modules\JNTT\Services;

use Illuminate\Support\Arr;
use Modules\JNTT\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class JNTTService implements JNTTServiceInterface
{
    /**
     * Map GHN status
     *
     * @param string $jnttStatus
     * @return string|null
     */
    public function mapStatus($jnttStatus)
    {
        return Arr::get([
            JNTTOrderStatus::PICKUP => Order::STATUS_DELIVERING,
            JNTTOrderStatus::DEPARTURE => Order::STATUS_DELIVERING,
            JNTTOrderStatus::ARRIVAL => Order::STATUS_DELIVERING,
            JNTTOrderStatus::ON_DELIVERY => Order::STATUS_DELIVERING,
            JNTTOrderStatus::SELF_COLLECTION => Order::STATUS_DELIVERING,
            JNTTOrderStatus::SIGNATURE => Order::STATUS_DELIVERED,
            JNTTOrderStatus::PROBLEMATIC => Order::STATUS_ERROR,
            JNTTOrderStatus::Storage => Order::STATUS_ERROR,
            JNTTOrderStatus::RETURN => Order::STATUS_RETURNING,
            JNTTOrderStatus::RETURN_CONFIRMATION => Order::STATUS_RETURNING,
            JNTTOrderStatus::RETURN_SIGNATURE => Order::STATUS_RETURNED,
        ], $jnttStatus);
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
