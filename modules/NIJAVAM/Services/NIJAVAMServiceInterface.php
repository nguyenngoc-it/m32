<?php /** @noinspection ALL */

namespace Modules\NIJAVAM\Services;

use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

interface NIJAVAMServiceInterface
{
    /**
     * Map NIJAVAM status
     *
     * @param string $NIJAVAMStatus
     * @return string|null
     */
    public function mapStatus($NIJAVAMStatus);

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
