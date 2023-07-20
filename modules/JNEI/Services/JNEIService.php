<?php

namespace Modules\JNEI\Services;

use Illuminate\Support\Arr;
use Modules\Order\Models\OrderItem;
use Modules\JNEI\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class JNEIService implements JNEIServiceInterface
{
    /**
     * Map GHN status
     *
     * @param string $jneiStatus
     * @return string|null
     */
    public function mapStatus($jneiStatus)
    {
        return Arr::get([
            JNEIOrderStatus::DELIVERING_IP1 => Order::STATUS_DELIVERING,
            JNEIOrderStatus::DELIVERING_IP2 => Order::STATUS_DELIVERING,
            JNEIOrderStatus::DELIVERING_OP1 => Order::STATUS_DELIVERING,
            JNEIOrderStatus::DELIVERING_OP2 => Order::STATUS_DELIVERING,
            JNEIOrderStatus::DELIVERING_OP3 => Order::STATUS_DELIVERING,
            JNEIOrderStatus::DELIVERING_RC1 => Order::STATUS_DELIVERING,
            JNEIOrderStatus::DELIVERING_TP4 => Order::STATUS_DELIVERING,
            JNEIOrderStatus::DELIVERED_D01 => Order::STATUS_DELIVERED,
        ], $jneiStatus);
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

        return substr($totalQuantity . ' PCS-' . $contentRemark . '-' . $order->cod, 0, 59);
    }
}
