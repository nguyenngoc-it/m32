<?php

namespace Modules\JNTC\Services;

use Illuminate\Support\Arr;
use Modules\JNTC\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class JNTCService implements JNTCServiceInterface
{
    /**
     * Map GHN status
     *
     * @param string $jntcStatus
     * @return string|null
     */
    public function mapStatus($jntcStatus)
    {
        return Arr::get([
            JNTCOrderStatus::PICKUP => Order::STATUS_PICKED_UP,
            JNTCOrderStatus::ARRIVAL => Order::STATUS_DELIVERING,
            JNTCOrderStatus::DEPARTURE => Order::STATUS_DELIVERING,
            JNTCOrderStatus::DEPARTURE => Order::STATUS_DELIVERING,
            JNTCOrderStatus::DELIVERED => Order::STATUS_DELIVERED,
            JNTCOrderStatus::PROBLEM_PACKAGE => Order::STATUS_ERROR,
            JNTCOrderStatus::ON_HOLD => Order::STATUS_ERROR,
            JNTCOrderStatus::RETURN => Order::STATUS_RETURNING,
            JNTCOrderStatus::RETURN_POD => Order::STATUS_RETURNED,
        ], $jntcStatus);
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
