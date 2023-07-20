<?php

namespace Modules\GGE\Jobs;

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
     * @var int
     */
    protected $userId;

    /**
     * SyncOrderStatusJob constructor
     *
     * @param array $input
     * @param int $userId
     */
    public function __construct(array $input, $userId)
    {
        $this->input = $input;
        $this->userId = $userId;
    }

    /**
     * @throws ShippingPartnerException
     */
    public function handle()
    {
        Service::GGE()->syncOrderStatus($this->input, User::find($this->userId));
    }
}
