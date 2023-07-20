<?php

namespace Modules\GGE\Services;

use Illuminate\Support\Arr;
use Modules\GGE\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class GGEService implements GGEServiceInterface
{
    /**
     * Map GGE status
     *
     * @param string $GGEStatus
     * @return string|null
     */
    public function mapStatus($GGEStatus)
    {
        return Arr::get([
            GGEOrderStatus::PENDING                   => Order::STATUS_READY_TO_PICK,
            GGEOrderStatus::FOR_PICKUP                => Order::STATUS_READY_TO_PICK,
            GGEOrderStatus::PICKUP_RIDER_FOUND        => Order::STATUS_READY_TO_PICK,
            GGEOrderStatus::OUT_FOR_PICKUP            => Order::STATUS_READY_TO_PICK,
            GGEOrderStatus::FAILED_PICKUP_ATTEMPT     => Order::STATUS_READY_TO_PICK,
            GGEOrderStatus::CANCELED                  => Order::STATUS_CANCEL,
            GGEOrderStatus::FAILED_PICKUP             => Order::STATUS_READY_TO_PICK,
            GGEOrderStatus::PICKED_UP                 => Order::STATUS_PICKED_UP,
            GGEOrderStatus::RECEIVED_AT_PICKUP_HUB    => Order::STATUS_PICKED_UP,
            GGEOrderStatus::IN_TRANSIT                => Order::STATUS_DELIVERING,
            GGEOrderStatus::AT_SORTING_CENTER         => Order::STATUS_DELIVERING,
            GGEOrderStatus::RECEIVED_AT_DELIVERY_AREA => Order::STATUS_DELIVERING,
            GGEOrderStatus::RECEIVED_AT_DELIVERY_HUB  => Order::STATUS_DELIVERING,
            GGEOrderStatus::OUT_FOR_DELIVERY          => Order::STATUS_DELIVERING,
            GGEOrderStatus::DELIVERED                 => Order::STATUS_DELIVERED,
            GGEOrderStatus::FAILED_DELIVERY           => Order::STATUS_DELIVERING,
            GGEOrderStatus::FOR_RETURN                => Order::STATUS_RETURNING,
            GGEOrderStatus::ARRIVED_AT_RTS_HUB        => Order::STATUS_RETURNING,
            GGEOrderStatus::OUT_FOR_RETURN            => Order::STATUS_RETURNING,
            GGEOrderStatus::RETURN_IN_TRANSIT         => Order::STATUS_RETURNING,
            GGEOrderStatus::RETURNED                  => Order::STATUS_RETURNED,
            GGEOrderStatus::FAILED_RETURN             => Order::STATUS_RETURNING,
            GGEOrderStatus::FOR_CLAIMS                => Order::STATUS_ERROR,
            GGEOrderStatus::CLAIMED                   => Order::STATUS_ERROR,
        ], $GGEStatus, Order::STATUS_READY_TO_PICK);
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
        // return (new SyncOrderStatus($input, $user))->handle();
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
        $items = $order->items->map(function (OrderItem $orderItem) use (&$contentRemark) {
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
        $totalQuantity = $countQuantityItems ?: 1;

        return $totalQuantity . ' PCS-FIN-' . $contentRemark . '-' . $order->cod;
    }
}
