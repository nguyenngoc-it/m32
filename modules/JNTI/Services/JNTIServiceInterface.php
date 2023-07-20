<?php

namespace Modules\JNTI\Services;

use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

interface JNTIServiceInterface
{
    /**
     * Map JNTI status
     *
     * @param string $JNTIStatus
     * @return string|null
     */
    public function mapStatus($JNTIStatus);

    /**
     * Sync delivery status from GHN order status
     *
     * @param array $input
     * @param User $user
     * @return Order
     * @throws ShippingPartnerException
     */
    public function syncOrderStatus(array $input, User $user);

    /**
     * Get order remark
     *
     * @param Order $order
     * @return string
     */
    public function getRemark(Order $order);
}
