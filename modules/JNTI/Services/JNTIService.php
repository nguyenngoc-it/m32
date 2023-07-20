<?php

namespace Modules\JNTI\Services;

use Illuminate\Support\Arr;
use Modules\JNTI\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class JNTIService implements JNTIServiceInterface
{
    /**
     * Map GHN status
     *
     * @param string $jntiStatus
     * @return string|null
     */
    public function mapStatus($jntiStatus)
    {
        return Arr::get([
            JNTIOrderStatus::ORDER_CREATED => Order::STATUS_READY_TO_PICK,
            JNTIOrderStatus::ERROR_PICKUP => Order::STATUS_ERROR,
            JNTIOrderStatus::DELIVERING => Order::STATUS_DELIVERING,
            JNTIOrderStatus::DELIVERY_FAILED => Order::STATUS_ERROR,
            JNTIOrderStatus::DELIVERED => Order::STATUS_DELIVERED,
            JNTIOrderStatus::ERROR_GOOD => Order::STATUS_ERROR,
            JNTIOrderStatus::RETURN => Order::STATUS_RETURNING,
            JNTIOrderStatus::RETURNED => Order::STATUS_RETURNED,
            JNTIOrderStatus::CANNCED_BY_API => Order::STATUS_CANCEL,
            JNTIOrderStatus::CANNCED_BY_JNT => Order::STATUS_CANCEL,
        ], $jntiStatus);
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
