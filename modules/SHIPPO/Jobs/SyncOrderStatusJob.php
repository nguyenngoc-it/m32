<?php

namespace Modules\SHIPPO\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\User\Models\User;

class SyncOrderStatusJob extends Job
{
    public $queue = 'webhook_sync_order';

    /**
     * @var array
     */
    protected $input;

    /**
     * SyncOrderStatusJob constructor
     *
     * @param array $input
     */
    public function __construct(array $input)
    {
        $this->input = $input;
    }

    /**
     * @throws ShippingPartnerException
     */
    public function handle()
    {
        /** @var User $shippoUser */
        $shippoUser = User::query()->where('username', 'shippo')->first();
        Service::shippo()->syncOrderStatus($this->input, $shippoUser);
    }
}
