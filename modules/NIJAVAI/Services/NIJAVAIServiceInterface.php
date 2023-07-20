<?php

namespace Modules\NIJAVAI\Services;

use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

interface NIJAVAIServiceInterface
{
    /**
     * Map NIJAVAI status
     *
     * @param string $nijavaiStatus
     * @return string|null
     */
    public function mapStatus($nijavaiStatus);

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
