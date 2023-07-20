<?php

namespace Modules\JNTM\Services;

use Illuminate\Support\Arr;
use Modules\FLASH\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class JNTMService implements JNTMServiceInterface
{
    /**
     * Map JNTM status
     *
     * @param string $jntmStatus
     * @return string|null
     */
    public function mapStatus($jntmStatus)
    {
        return Arr::get([
            JNTMOrderStatus::PICKED_UP => Order::STATUS_PICKED_UP,
            JNTMOrderStatus::DEPARTURE => Order::STATUS_DELIVERING,
            JNTMOrderStatus::ON_DELIVERY => Order::STATUS_DELIVERING,
            JNTMOrderStatus::ON_HOLD => Order::STATUS_ERROR,
            JNTMOrderStatus::DELIVERED => Order::STATUS_DELIVERED,
            JNTMOrderStatus::ON_RETURN => Order::STATUS_RETURNING,
            JNTMOrderStatus::RETURN_SIGNATURE => Order::STATUS_RETURNED,
            JNTMOrderStatus::ARRIVED => Order::STATUS_DELIVERING,
        ], $jntmStatus);
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
