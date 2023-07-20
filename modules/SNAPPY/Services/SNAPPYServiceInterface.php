<?php
namespace Modules\SNAPPY\Services;

use Modules\Order\Models\Order;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

interface SNAPPYServiceInterface
{
    /**
     * Map SNAPPY status
     *
     * @param string $snappyStatus
     * @return string|null
     */
    public function mapStatus(string $snappyStatus): ?string;

    /**
     * Sync delivery status from SNAPPY order status
     *
     * @param array $input
     * @param User $user
     * @return Order
     * @throws ShippingPartnerException
     */
    public function syncOrderStatus(array $input, User $user): Order;
}
