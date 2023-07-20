<?php

namespace Modules\FLASH\Services;

use Illuminate\Support\Arr;
use Modules\FLASH\Commands\SyncOrderStatus;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class FLASHService implements FLASHServiceInterface
{
    /**
     * Map FLASH status
     *
     * @param string $flashStatus
     * @return string|null
     */
    public function mapStatus($flashStatus)
    {
        return Arr::get([
            FLASHOrderStatus::PICKUP                 => Order::STATUS_DELIVERING,
            FLASHOrderStatus::IN_TRANSIT             => Order::STATUS_DELIVERING,
            FLASHOrderStatus::ON_DELIVERY            => Order::STATUS_DELIVERING,
            FLASHOrderStatus::DETAINED               => Order::STATUS_DELIVERING,
            FLASHOrderStatus::DELIVERED              => Order::STATUS_DELIVERED,
            FLASHOrderStatus::PROBLEMATIC_PROCESSING => Order::STATUS_ERROR,
            FLASHOrderStatus::RETURNED               => Order::STATUS_RETURNED,
            FLASHOrderStatus::CLOSED                 => Order::STATUS_DELIVERED,
            FLASHOrderStatus::CANCELLED              => Order::STATUS_CANCEL
        ], $flashStatus);
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
