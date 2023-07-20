<?php

namespace Modules\GHN\Services;

use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

interface GHNServiceInterface
{
    /**
     * Map GHN status
     *
     * @param string $ghnStatus
     * @return string|null
     */
    public function mapStatus($ghnStatus);

    /**
     * Sync delivery status from GHN order status
     *
     * @param array $input
     * @param User $user
     * @return Order
     * @throws ShippingPartnerException
     */
    public function syncOrderStatus(array $input, User $user);
}