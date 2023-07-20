<?php /** @noinspection PhpReturnDocTypeMismatchInspection */

namespace Modules\LWE\Commands;

use Gobiz\Log\LogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncOrderStatus
{
    /**
     * @var array
     */
    protected $input = [];

    /**
     * @var User
     */
    protected $user;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SyncOrderStatus constructor
     *
     * @param array $input
     * @param User $user
     */
    public function __construct(array $input, User $user)
    {
        $this->input  = $input;
        $this->user   = $user;
        $this->logger = LogService::logger('lwe_webhook', [
            'context' => ['order' => $this->input],
        ]);
    }

    /**
     * @return void
     */
    public function handle()
    {
        $this->logger->info('WEBHOOK.RECEIVED');
        $referenceNumber = Arr::get($this->input, 'referenceNumber');
        $status          = Arr::get($this->input, 'package_status_id');

        if (empty($referenceNumber) || empty($status)) {
            $this->logger->error('WEBHOOK.EMPTY_DATA');
            return;
        }

        if (!$order = $this->findOrder($referenceNumber)) {
            $this->logger->error('WEBHOOK.ORDER_NOT_FOUND');
            return;
        }
        $orderCode = $order->code;
        $order->update(['original_status' => $status]);

        if (!$orderStatus = Service::lwe()->mapStatus($status)) {
            $this->logger->error('WEBHOOK.CANT_MAP_STATUS', [
                'order' => $orderCode,
                'lwe_status' => $status,
            ]);
            return;
        }

        if ($order->status === $orderStatus) {
            return;
        }

        $order->changeStatusWithoutFlow($orderStatus);
    }

    /**
     * @param string $referenceNumber
     * @return Builder|Model|Order|object|null
     */
    protected function findOrder(string $referenceNumber)
    {
        return Order::query()->where([
            'shipping_partner_code' => ShippingPartner::PARTNER_LWE,
            'ref' => $referenceNumber,
        ])->first();
    }
}
