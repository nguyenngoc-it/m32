<?php /** @noinspection ALL */

namespace Modules\NIJAVAP\Services;

use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

interface NIJAVAPServiceInterface
{
    /**
     * Map NIJAVAP status
     *
     * @param string $NIJAVAPStatus
     * @return string|null
     */
    public function mapStatus($NIJAVAPStatus);

    /**
     * Sync delivery status from NinjavanP order status
     *
     * @param array $input
     * @param User $user
     * @return Order
     * @throws ShippingPartnerException
     */
    public function syncOrderStatus(array $input, User $user);
}
