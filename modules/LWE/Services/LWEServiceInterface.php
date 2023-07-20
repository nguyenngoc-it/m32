<?php

namespace Modules\LWE\Services;

use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

interface LWEServiceInterface
{
    /**
     * Map LWE status
     *
     * @param string $lweStatus
     * @return string|null
     */
    public function mapStatus($lweStatus);

    /**
     * Sync delivery status from LWE order status
     *
     * @param array $input
     * @param User $user
     * @return void
     * @throws ShippingPartnerException
     */
    public function syncOrderStatus(array $input, User $user);
}
