<?php

namespace Modules\SHIPPO\Services;

use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

interface SHIPPOServiceInterface
{
    /**
     * Map SHIPPO status
     *
     * @param string $shippoStatus
     * @return string|null
     */
    public function mapStatus($shippoStatus);

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
