<?php

namespace Modules\SAPI\Services;

use Illuminate\Support\Arr;
use Modules\Order\Models\OrderItem;
use Modules\SAPI\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class SAPIService implements SAPIServiceInterface
{
    /**
     * Map GHN status
     *
     * @param string $sapiStatus
     * @return string|null
     */
    public function mapStatus($sapiStatus)
    {
        return Arr::get([
            SAPIOrderStatus::PICKUP => Order::STATUS_READY_TO_PICK,
            SAPIOrderStatus::VERIFIED => Order::STATUS_PICKED_UP,
            SAPIOrderStatus::MANIFEST_OUTGOING => Order::STATUS_PICKED_UP,
            SAPIOrderStatus::OUTGOING_SMU => Order::STATUS_PICKED_UP,
            SAPIOrderStatus::INCOMING => Order::STATUS_DELIVERING,
            SAPIOrderStatus::DELIVERY => Order::STATUS_DELIVERING,
            SAPIOrderStatus::DELIVERED => Order::STATUS_DELIVERED,
            SAPIOrderStatus::UNDELIVERED => Order::STATUS_ERROR,
            SAPIOrderStatus::ANTAR_ULANG => Order::STATUS_RETURNING,
            SAPIOrderStatus::RETURN => Order::STATUS_RETURNING,
            SAPIOrderStatus::RETURN_TO_CLIENT => Order::STATUS_RETURNED,
        ], $sapiStatus);
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

        return substr($totalQuantity . ' PCS-' . $contentRemark . '-' . $order->cod, 0, 199);
    }
}
