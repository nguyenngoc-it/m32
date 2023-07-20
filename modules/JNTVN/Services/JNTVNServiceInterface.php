<?php

namespace Modules\JNTVN\Services;

use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

interface JNTVNServiceInterface
{
    /**
     * Map GHN status
     *
     * @param string $jntvnStatus
     * @return string|null
     */
    public function mapStatus($jntvnStatus);

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
